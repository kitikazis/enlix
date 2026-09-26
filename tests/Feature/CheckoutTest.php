<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EstadoPago;
use App\Enums\MetodoPago;
use App\Mail\PedidoPagadoAdmin;
use App\Mail\PedidoPagadoCliente;
use App\Models\Carrito;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'izipay.username' => 'test-user',
            'izipay.password' => 'test-password-secreta',
            'izipay.public_key' => 'test-public-key',
            'izipay.sha256_key' => 'test-sha256-key-secreta',
            'izipay.currency' => 'PEN',
        ]);
    }

    private function producto(int $stock = 5, int $precioCentimos = 150000): Producto
    {
        return Producto::create([
            'slug' => 'gpu-checkout-test',
            'nombre' => 'GPU de prueba',
            'descripcion' => 'x',
            'sku' => 'GPU-TEST-01',
            'precio_centimos' => $precioCentimos,
            'stock' => $stock,
            'activo' => true,
        ]);
    }

    /** Agrega al carrito y devuelve el session_id real (via credentials, ver nota en CarritoTest). */
    private function carritoConItem(Producto $producto, int $cantidad = 1): string
    {
        $this->postJson(route('carrito.items.store'), ['producto_id' => $producto->id, 'cantidad' => $cantidad]);

        return Carrito::sole()->session_id;
    }

    private function datosCliente(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'email' => 'juan@example.com',
            'telefono' => '+51999999999',
            'tipo_documento' => 'DNI',
            'numero_documento' => '12345678',
            'tipo_comprobante' => 'boleta',
            'razon_social' => '',
            'metodo_entrega' => 'recojo',
            'direccion' => '',
            'distrito' => '',
            'ciudad' => '',
            'referencia' => '',
            'terminos' => true,
        ], $overrides);
    }

    /**
     * Patrón específico del endpoint (no 'api.micuentaweb.pe/*'): un patrón
     * amplio registrado primero interceptaría también las llamadas a
     * V4/Order/Get de los tests del comando de expiración (Http::fake()
     * usa el primer stub que matchea, no el último registrado).
     */
    private function fakeFormToken(string $formToken = 'tok_fake_123'): void
    {
        Http::fake([
            '*/V4/Charge/CreatePayment' => Http::response([
                'status' => 'SUCCESS',
                'answer' => ['formToken' => $formToken, 'publicKey' => 'pk_test'],
            ], 200),
        ]);
    }

    private function krAnswer(string $codigo, int $totalCentimos, array $overrides = []): array
    {
        return array_replace_recursive([
            'orderStatus' => 'PAID',
            'orderDetails' => [
                'orderId' => $codigo,
                'orderTotalAmount' => $totalCentimos,
                'orderCurrency' => 'PEN',
            ],
            'transactions' => [
                [
                    'uuid' => 'uuid-1234',
                    'status' => 'CAPTURED',
                    'detailedStatus' => 'CAPTURED',
                    'transactionDetails' => [
                        'cardDetails' => ['pan' => '497010XXXXXX0000', 'effectiveBrand' => 'VISA'],
                    ],
                ],
            ],
        ], $overrides);
    }

    /** @return array{'kr-answer': string, 'kr-hash': string} */
    private function firmar(array $answer, string $llave): array
    {
        $raw = json_encode($answer);

        return ['kr-answer' => $raw, 'kr-hash' => hash_hmac('sha256', $raw, $llave)];
    }

    public function test_crear_pedido_usa_el_precio_del_servidor_y_reserva_stock(): void
    {
        $producto = $this->producto(stock: 10, precioCentimos: 150000);
        $sessionId = $this->carritoConItem($producto, 2);
        $this->fakeFormToken();

        $r = $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente());

        $r->assertOk()->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => $request['amount'] === 300000 && $request['currency'] === 'PEN');

        $this->assertDatabaseHas('pedidos', [
            'total_centimos' => 300000,
            'estado_pago' => 'pendiente',
        ]);

        $producto->refresh();
        $this->assertSame(2, $producto->stock_reservado);
        $this->assertSame(8, $producto->stockDisponible());

        // El carrito se vació al confirmar el checkout.
        $this->assertSame(0, Carrito::sole()->items()->count());
    }

    public function test_crear_pedido_envia_ipn_target_url_absoluta_al_checkout(): void
    {
        $producto = $this->producto();
        $sessionId = $this->carritoConItem($producto, 1);
        $this->fakeFormToken();

        $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente());

        Http::assertSent(fn ($request) => $request['ipnTargetUrl'] === route('checkout.ipn')
            && str_starts_with($request['ipnTargetUrl'], 'http'));
    }

    public function test_no_deja_pagar_mas_del_stock_disponible(): void
    {
        $producto = $this->producto(stock: 1);
        $sessionId = $this->carritoConItem($producto, 1);

        // Otro cliente agota el stock despues de que el primero agrego al carrito.
        $producto->update(['stock' => 0]);

        $this->fakeFormToken();

        $r = $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente());

        $r->assertStatus(422)->assertJson(['ok' => false]);
        $this->assertDatabaseCount('pedidos', 0);
        Http::assertNothingSent();
    }

    public function test_si_izipay_rechaza_el_form_token_se_libera_el_stock_y_el_carrito_no_se_pierde(): void
    {
        $producto = $this->producto(stock: 5);
        $sessionId = $this->carritoConItem($producto, 1);

        Http::fake(['api.micuentaweb.pe/*' => Http::response(['status' => 'ERROR'], 200)]);

        $r = $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente());

        $r->assertStatus(422)->assertJson(['ok' => false]);

        $this->assertDatabaseHas('pedidos', ['estado_pago' => 'rechazado']);

        $producto->refresh();
        $this->assertSame(0, $producto->stock_reservado);

        // El carrito sigue con su item: el cliente puede reintentar sin rearmar todo.
        $this->assertSame(1, Carrito::sole()->items()->count());
    }

    public function test_el_ipn_confirma_el_pago_y_descuenta_stock_fisico(): void
    {
        $producto = $this->producto(stock: 10);
        $sessionId = $this->carritoConItem($producto, 3);
        $this->fakeFormToken();

        $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente());

        $pedido = Pedido::sole();
        $answer = $this->krAnswer($pedido->codigo, $pedido->total_centimos);
        $firma = $this->firmar($answer, 'test-password-secreta');

        $r = $this->postJson(route('checkout.ipn'), [
            'kr-answer' => $firma['kr-answer'],
            'kr-hash' => $firma['kr-hash'],
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]);

        $r->assertOk();

        $pedido->refresh();
        $this->assertTrue($pedido->estado_pago === EstadoPago::Pagado);
        $this->assertNotNull($pedido->pagado_en);
        $this->assertSame(MetodoPago::Tarjeta, $pedido->metodo_pago);

        $producto->refresh();
        $this->assertSame(7, $producto->stock);
        $this->assertSame(0, $producto->stock_reservado);

        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $producto->id,
            'tipo' => 'salida',
            'cantidad' => 3,
            'referencia' => $pedido->codigo,
        ]);
    }

    public function test_ipn_repetido_no_descuenta_stock_dos_veces(): void
    {
        $producto = $this->producto(stock: 10);
        $sessionId = $this->carritoConItem($producto, 2);
        $this->fakeFormToken();

        $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente());

        $pedido = Pedido::sole();
        $answer = $this->krAnswer($pedido->codigo, $pedido->total_centimos);
        $firma = $this->firmar($answer, 'test-password-secreta');
        $payload = [
            'kr-answer' => $firma['kr-answer'],
            'kr-hash' => $firma['kr-hash'],
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ];

        $this->postJson(route('checkout.ipn'), $payload);
        $this->postJson(route('checkout.ipn'), $payload);
        $this->postJson(route('checkout.ipn'), $payload);

        $producto->refresh();
        $this->assertSame(8, $producto->stock);
        $this->assertSame(1, Pedido::count());
    }

    public function test_el_ipn_envia_email_de_confirmacion_al_cliente(): void
    {
        Mail::fake();
        $producto = $this->producto(stock: 5);
        $sessionId = $this->carritoConItem($producto, 1);
        $this->fakeFormToken();

        $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente(['email' => 'cliente@example.com']));

        $pedido = Pedido::sole();
        $answer = $this->krAnswer($pedido->codigo, $pedido->total_centimos);
        $firma = $this->firmar($answer, 'test-password-secreta');

        $this->postJson(route('checkout.ipn'), [
            'kr-answer' => $firma['kr-answer'],
            'kr-hash' => $firma['kr-hash'],
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]);

        Mail::assertQueued(PedidoPagadoCliente::class, fn ($mail) => $mail->pedido->id === $pedido->id
            && $mail->hasTo('cliente@example.com'));
    }

    public function test_el_ipn_envia_email_al_admin_si_esta_configurado(): void
    {
        config(['tienda.admin_email' => 'admin@enlix.pe']);
        Mail::fake();
        $producto = $this->producto(stock: 5);
        $sessionId = $this->carritoConItem($producto, 1);
        $this->fakeFormToken();

        $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente());

        $pedido = Pedido::sole();
        $answer = $this->krAnswer($pedido->codigo, $pedido->total_centimos);
        $firma = $this->firmar($answer, 'test-password-secreta');

        $this->postJson(route('checkout.ipn'), [
            'kr-answer' => $firma['kr-answer'],
            'kr-hash' => $firma['kr-hash'],
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]);

        Mail::assertQueued(PedidoPagadoAdmin::class, fn ($mail) => $mail->hasTo('admin@enlix.pe'));
    }

    public function test_no_envia_email_al_admin_si_no_esta_configurado(): void
    {
        config(['tienda.admin_email' => null]);
        Mail::fake();
        $producto = $this->producto(stock: 5);
        $sessionId = $this->carritoConItem($producto, 1);
        $this->fakeFormToken();

        $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente());

        $pedido = Pedido::sole();
        $answer = $this->krAnswer($pedido->codigo, $pedido->total_centimos);
        $firma = $this->firmar($answer, 'test-password-secreta');

        $this->postJson(route('checkout.ipn'), [
            'kr-answer' => $firma['kr-answer'],
            'kr-hash' => $firma['kr-hash'],
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]);

        Mail::assertNotQueued(PedidoPagadoAdmin::class);
    }

    public function test_ipn_repetido_no_reenvia_el_email_de_confirmacion(): void
    {
        Mail::fake();
        $producto = $this->producto(stock: 5);
        $sessionId = $this->carritoConItem($producto, 1);
        $this->fakeFormToken();

        $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente());

        $pedido = Pedido::sole();
        $answer = $this->krAnswer($pedido->codigo, $pedido->total_centimos);
        $firma = $this->firmar($answer, 'test-password-secreta');
        $payload = [
            'kr-answer' => $firma['kr-answer'],
            'kr-hash' => $firma['kr-hash'],
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ];

        $this->postJson(route('checkout.ipn'), $payload);
        $this->postJson(route('checkout.ipn'), $payload);
        $this->postJson(route('checkout.ipn'), $payload);

        Mail::assertQueuedCount(1);
    }

    public function test_el_retorno_del_navegador_nunca_marca_pagado(): void
    {
        $producto = $this->producto(stock: 5);
        $sessionId = $this->carritoConItem($producto, 1);
        $this->fakeFormToken();

        $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente());

        $pedido = Pedido::sole();
        $answer = $this->krAnswer($pedido->codigo, $pedido->total_centimos);
        $firma = $this->firmar($answer, 'test-sha256-key-secreta');

        $r = $this->postJson(route('checkout.validar'), [
            'kr-answer' => $firma['kr-answer'],
            'kr-hash' => $firma['kr-hash'],
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'sha256_hmac',
        ]);

        $r->assertStatus(202)->assertJson(['ok' => false, 'pendiente' => true]);

        $pedido->refresh();
        $this->assertTrue($pedido->estado_pago === EstadoPago::EnVerificacion);

        // Nada se movio del stock todavia: eso solo lo hace el IPN.
        $producto->refresh();
        $this->assertSame(1, $producto->stock_reservado);
    }

    public function test_ipn_con_firma_invalida_no_marca_pagado(): void
    {
        $producto = $this->producto(stock: 5);
        $sessionId = $this->carritoConItem($producto, 1);
        $this->fakeFormToken();

        $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente());

        $pedido = Pedido::sole();
        $answer = $this->krAnswer($pedido->codigo, $pedido->total_centimos);

        $r = $this->postJson(route('checkout.ipn'), [
            'kr-answer' => json_encode($answer),
            'kr-hash' => 'firma-invalida',
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]);

        $r->assertOk();

        $pedido->refresh();
        $this->assertTrue($pedido->estado_pago === EstadoPago::Pendiente);
    }

    public function test_ipn_con_monto_distinto_no_marca_pagado(): void
    {
        $producto = $this->producto(stock: 5);
        $sessionId = $this->carritoConItem($producto, 1);
        $this->fakeFormToken();

        $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente());

        $pedido = Pedido::sole();
        $answer = $this->krAnswer($pedido->codigo, 1);
        $firma = $this->firmar($answer, 'test-password-secreta');

        $this->postJson(route('checkout.ipn'), [
            'kr-answer' => $firma['kr-answer'],
            'kr-hash' => $firma['kr-hash'],
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]);

        $pedido->refresh();
        $this->assertTrue($pedido->estado_pago === EstadoPago::Pendiente);
    }

    public function test_no_se_puede_iniciar_checkout_con_el_carrito_vacio(): void
    {
        $this->fakeFormToken();

        $r = $this->postJson(route('checkout.crear'), $this->datosCliente());

        $r->assertStatus(422)->assertJson(['ok' => false]);
        Http::assertNothingSent();
    }

    public function test_valida_dni_de_8_digitos(): void
    {
        $producto = $this->producto();
        $sessionId = $this->carritoConItem($producto, 1);

        $r = $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente(['numero_documento' => '123']));

        $r->assertStatus(422);
    }

    public function test_checkout_renderiza_con_items_del_carrito(): void
    {
        $producto = $this->producto();
        $sessionId = $this->carritoConItem($producto, 1);

        $r = $this->withCookie('carrito_session', $sessionId)->get('/checkout');

        $r->assertOk();
        $r->assertSee('GPU de prueba');
        $r->assertSee('Continuar al pago');
    }

    public function test_checkout_redirige_al_carrito_si_esta_vacio(): void
    {
        $r = $this->get('/checkout');

        $r->assertRedirect(route('carrito.index'));
    }

    private function pedidoPendienteViejo(int $stock = 5, int $cantidad = 1): Pedido
    {
        $producto = $this->producto(stock: $stock);
        $sessionId = $this->carritoConItem($producto, $cantidad);
        $this->fakeFormToken();

        $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente());

        $pedido = Pedido::sole();
        $pedido->forceFill(['created_at' => now()->subMinutes(21)])->save();

        return $pedido->fresh();
    }

    public function test_comando_libera_reservas_de_pedidos_abandonados(): void
    {
        $pedido = $this->pedidoPendienteViejo(stock: 5, cantidad: 2);
        $item = $pedido->items->first();

        Http::fake([
            '*/V4/Order/Get' => Http::response(['status' => 'ERROR', 'answer' => ['errorCode' => 'PSP_010']], 200),
        ]);

        $this->artisan('pedidos:liberar-reservas-expiradas')->assertSuccessful();

        $this->assertTrue($pedido->fresh()->estado_pago === EstadoPago::Expirado);
        $this->assertSame(0, $item->producto->fresh()->stock_reservado);
    }

    public function test_comando_de_liberacion_rescata_un_pedido_que_si_se_cobro(): void
    {
        $pedido = $this->pedidoPendienteViejo();

        Http::fake([
            '*/V4/Order/Get' => Http::response([
                'status' => 'SUCCESS',
                'answer' => [
                    'orderStatus' => 'PAID',
                    'orderDetails' => [
                        'orderId' => $pedido->izipay_order_id,
                        'orderTotalAmount' => $pedido->total_centimos,
                        'orderCurrency' => 'PEN',
                    ],
                    'transactions' => [['uuid' => 'uuid-rescatado', 'detailedStatus' => 'CAPTURED']],
                ],
            ], 200),
        ]);

        $this->artisan('pedidos:liberar-reservas-expiradas')->assertSuccessful();

        // Nunca se expira a ciegas: si Izipay dice que se cobró, se cobra.
        $this->assertTrue($pedido->fresh()->estado_pago === EstadoPago::Pagado);
    }

    public function test_comando_de_liberacion_no_toca_nada_si_izipay_no_responde(): void
    {
        $pedido = $this->pedidoPendienteViejo();

        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $this->artisan('pedidos:liberar-reservas-expiradas')->assertSuccessful();

        $this->assertTrue($pedido->fresh()->estado_pago === EstadoPago::Pendiente);
    }

    public function test_no_deja_pagar_sin_aceptar_terminos(): void
    {
        $producto = $this->producto();
        $sessionId = $this->carritoConItem($producto, 1);
        $this->fakeFormToken();

        $r = $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente(['terminos' => false]));

        $r->assertStatus(422)->assertJsonValidationErrors('terminos');
        $this->assertDatabaseCount('pedidos', 0);
        Http::assertNothingSent();
    }

    public function test_pagina_de_terminos_responde_ok(): void
    {
        $this->get(route('terminos'))->assertOk();
    }

    public function test_envio_gratis_por_defecto_no_cobra_nada(): void
    {
        config(['tienda.envio.modo' => 'gratis']);
        $producto = $this->producto(precioCentimos: 10000);
        $sessionId = $this->carritoConItem($producto, 1);
        $this->fakeFormToken();

        $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente([
                'metodo_entrega' => 'envio',
                'direccion' => 'Av. Siempre Viva 123',
                'distrito' => 'Miraflores',
            ]));

        $pedido = Pedido::sole();
        $this->assertSame(0, $pedido->costo_envio_centimos);
        $this->assertSame(10000, $pedido->total_centimos);
    }

    public function test_envio_con_tarifa_fija_se_suma_al_total(): void
    {
        config(['tienda.envio.modo' => 'fijo', 'tienda.envio.tarifa_fija_centimos' => 1500]);
        $producto = $this->producto(precioCentimos: 10000);
        $sessionId = $this->carritoConItem($producto, 1);
        $this->fakeFormToken();

        $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente([
                'metodo_entrega' => 'envio',
                'direccion' => 'Av. Siempre Viva 123',
                'distrito' => 'Miraflores',
            ]));

        $pedido = Pedido::sole();
        $this->assertSame(1500, $pedido->costo_envio_centimos);
        $this->assertSame(11500, $pedido->total_centimos);

        Http::assertSent(fn ($request) => $request['amount'] === 11500);
    }

    public function test_recojo_en_tienda_nunca_cobra_envio_aunque_el_modo_sea_fijo(): void
    {
        config(['tienda.envio.modo' => 'fijo', 'tienda.envio.tarifa_fija_centimos' => 1500]);
        $producto = $this->producto(precioCentimos: 10000);
        $sessionId = $this->carritoConItem($producto, 1);
        $this->fakeFormToken();

        $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('checkout.crear'), $this->datosCliente(['metodo_entrega' => 'recojo']));

        $pedido = Pedido::sole();
        $this->assertSame(0, $pedido->costo_envio_centimos);
        $this->assertSame(10000, $pedido->total_centimos);
    }
}
