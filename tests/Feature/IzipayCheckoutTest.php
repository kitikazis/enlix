<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EstadoPago;
use App\Models\Pago;
use App\Support\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IzipayCheckoutTest extends TestCase
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

    private function primerProducto(): array
    {
        $slug = array_key_first(Producto::items());

        return Producto::find($slug);
    }

    private function crearPagoPendiente(array $overrides = []): Pago
    {
        $producto = $this->primerProducto();

        return Pago::create(array_merge([
            'producto' => $producto['slug'],
            'email' => 'cliente@example.com',
            'monto' => $producto['precio_centimos'],
            'moneda' => 'PEN',
            'izipay_order_id' => 'ENX-TEST-'.uniqid(),
            'estado' => EstadoPago::Pendiente,
        ], $overrides));
    }

    private function krAnswer(array $overrides = []): array
    {
        return array_replace_recursive([
            'orderStatus' => 'PAID',
            'orderDetails' => [
                'orderId' => 'ENX-TEST-0001',
                'orderTotalAmount' => 9900,
                'orderCurrency' => 'PEN',
            ],
            'transactions' => [
                [
                    'uuid' => 'uuid-1234',
                    'status' => 'CAPTURED',
                    // CAPTURED: dinero realmente cobrado. AUTHORISED (sin
                    // capturar) NO cuenta como pagado - ver EstadoPago.
                    'detailedStatus' => 'CAPTURED',
                    'transactionDetails' => [
                        'cardDetails' => [
                            'pan' => '497010XXXXXX0000',
                            'effectiveBrand' => 'VISA',
                        ],
                    ],
                ],
            ],
        ], $overrides);
    }

    /** @return array{'kr-answer': string, 'kr-hash': string} */
    private function firmar(array $answer, string $llave): array
    {
        $raw = json_encode($answer);

        return [
            'kr-answer' => $raw,
            'kr-hash' => hash_hmac('sha256', $raw, $llave),
        ];
    }

    private function fakeFormToken(string $formToken = 'tok_fake_123'): void
    {
        Http::fake([
            'api.micuentaweb.pe/*' => Http::response([
                'status' => 'SUCCESS',
                'answer' => ['formToken' => $formToken, 'publicKey' => 'pk_test'],
            ], 200),
        ]);
    }

    public function test_form_token_usa_siempre_el_precio_del_servidor(): void
    {
        $this->fakeFormToken();
        $producto = $this->primerProducto();

        $response = $this->postJson(route('izipay.form-token'), [
            'producto' => $producto['slug'],
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'email' => 'juan@example.com',
            'phone_number' => '+51999999999',
            'identity_code' => '12345678',
            // Un atacante intenta colar un monto/precio propio: debe ser ignorado.
            'monto' => 1,
            'precio_centimos' => 1,
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => $request['amount'] === $producto['precio_centimos']
            && $request['currency'] === 'PEN');

        $this->assertDatabaseHas('pagos', [
            'producto' => $producto['slug'],
            'monto' => $producto['precio_centimos'],
            'estado' => EstadoPago::Pendiente,
        ]);
    }

    public function test_form_token_con_status_error_en_body_http_200_no_se_trata_como_exito(): void
    {
        // Izipay responde HTTP 200 incluso con credenciales invalidas: el
        // error va dentro del body ("status":"ERROR"), no en el codigo HTTP.
        Http::fake([
            'api.micuentaweb.pe/*' => Http::response([
                'status' => 'ERROR',
                'answer' => ['errorCode' => 'INT_905', 'errorMessage' => 'invalid login or private key'],
            ], 200),
        ]);
        $producto = $this->primerProducto();

        $response = $this->postJson(route('izipay.form-token'), [
            'producto' => $producto['slug'],
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'email' => 'juan@example.com',
            'phone_number' => '+51999999999',
            'identity_code' => '12345678',
        ]);

        $response->assertStatus(422)->assertJson(['ok' => false]);
        $this->assertDatabaseMissing('pagos', ['producto' => $producto['slug']]);
    }

    public function test_moneda_distinta_no_marca_pagado(): void
    {
        $pago = $this->crearPagoPendiente();

        $answer = $this->krAnswer([
            'orderDetails' => [
                'orderId' => $pago->izipay_order_id,
                'orderTotalAmount' => $pago->monto,
                'orderCurrency' => 'USD',
            ],
        ]);
        $firma = $this->firmar($answer, config('izipay.password'));

        $this->postJson(route('izipay.ipn'), array_merge($firma, [
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]))->assertOk();

        $this->assertSame(EstadoPago::Pendiente, $pago->fresh()->estado);
    }

    public function test_monto_distinto_no_marca_pagado(): void
    {
        $pago = $this->crearPagoPendiente();

        $answer = $this->krAnswer([
            'orderDetails' => [
                'orderId' => $pago->izipay_order_id,
                'orderTotalAmount' => $pago->monto + 1,
                'orderCurrency' => $pago->moneda,
            ],
        ]);
        $firma = $this->firmar($answer, config('izipay.password'));

        $this->postJson(route('izipay.ipn'), array_merge($firma, [
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]))->assertOk();

        $this->assertSame(EstadoPago::Pendiente, $pago->fresh()->estado);
    }

    public function test_kr_hash_key_incorrecto_es_rechazado(): void
    {
        $pago = $this->crearPagoPendiente();

        $answer = $this->krAnswer([
            'orderDetails' => [
                'orderId' => $pago->izipay_order_id,
                'orderTotalAmount' => $pago->monto,
                'orderCurrency' => $pago->moneda,
            ],
        ]);
        // Firmado con la llave correcta pero declarando el kr-hash-key equivocado
        // (o firmado con la llave incorrecta para el canal usado).
        $firma = $this->firmar($answer, config('izipay.sha256_key'));

        $this->postJson(route('izipay.ipn'), array_merge($firma, [
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password', // IPN exige 'password', no 'sha256_hmac'
        ]))->assertOk();

        $this->assertSame(EstadoPago::Pendiente, $pago->fresh()->estado);
    }

    public function test_pago_pagado_no_regresa_a_rechazado_tras_unpaid_posterior(): void
    {
        $pago = $this->crearPagoPendiente(['estado' => EstadoPago::Pagado]);

        $answer = $this->krAnswer([
            'orderStatus' => 'UNPAID',
            'orderDetails' => [
                'orderId' => $pago->izipay_order_id,
                'orderTotalAmount' => $pago->monto,
                'orderCurrency' => $pago->moneda,
            ],
        ]);
        $firma = $this->firmar($answer, config('izipay.password'));

        $this->postJson(route('izipay.ipn'), array_merge($firma, [
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]))->assertOk();

        $this->assertSame(EstadoPago::Pagado, $pago->fresh()->estado);
    }

    public function test_ipn_repetido_cinco_veces_deja_un_solo_registro_y_estado_estable(): void
    {
        $pago = $this->crearPagoPendiente();

        $answer = $this->krAnswer([
            'orderDetails' => [
                'orderId' => $pago->izipay_order_id,
                'orderTotalAmount' => $pago->monto,
                'orderCurrency' => $pago->moneda,
            ],
        ]);
        $firma = $this->firmar($answer, config('izipay.password'));
        $payload = array_merge($firma, [
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('izipay.ipn'), $payload)->assertOk();
        }

        $this->assertSame(1, Pago::where('izipay_order_id', $pago->izipay_order_id)->count());
        $this->assertSame(EstadoPago::Pagado, $pago->fresh()->estado);
    }

    public function test_ipn_con_order_id_inexistente_responde_200_sin_crear_registro(): void
    {
        $totalAntes = Pago::count();

        $answer = $this->krAnswer([
            'orderDetails' => [
                'orderId' => 'ENX-NO-EXISTE',
                'orderTotalAmount' => 9900,
                'orderCurrency' => 'PEN',
            ],
        ]);
        $firma = $this->firmar($answer, config('izipay.password'));

        $this->postJson(route('izipay.ipn'), array_merge($firma, [
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]))->assertOk();

        $this->assertSame($totalAntes, Pago::count());
    }

    public function test_validar_con_order_id_inexistente_no_crea_registro(): void
    {
        $totalAntes = Pago::count();

        $answer = $this->krAnswer([
            'orderDetails' => [
                'orderId' => 'ENX-NO-EXISTE-BROWSER',
                'orderTotalAmount' => 9900,
                'orderCurrency' => 'PEN',
            ],
        ]);
        $firma = $this->firmar($answer, config('izipay.sha256_key'));

        $this->postJson(route('izipay.validar'), array_merge($firma, [
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'sha256_hmac',
        ]));

        $this->assertSame($totalAntes, Pago::count());
    }

    public function test_logs_no_contienen_form_token_ni_kr_hash(): void
    {
        $formTokenSecreto = 'tok_super_secreto_no_debe_verse_en_logs';
        $this->fakeFormToken($formTokenSecreto);

        $logPath = storage_path('logs/test-izipay-security.log');
        @unlink($logPath);
        config(['logging.channels.test_izipay' => ['driver' => 'single', 'path' => $logPath, 'level' => 'debug']]);
        config(['logging.default' => 'test_izipay']);

        $producto = $this->primerProducto();

        // Camino feliz: se genera un formToken (secreto) que jamas debe quedar en el log.
        $this->postJson(route('izipay.form-token'), [
            'producto' => $producto['slug'],
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'email' => 'juan@example.com',
            'phone_number' => '+51999999999',
            'identity_code' => '12345678',
        ])->assertOk();

        // Camino de error: firma invalida (ejercita el Log::warning con kr-hash disponible).
        $firmaInvalida = 'firma-claramente-invalida-0000';
        $this->postJson(route('izipay.validar'), [
            'kr-answer' => json_encode($this->krAnswer()),
            'kr-hash' => $firmaInvalida,
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'sha256_hmac',
        ]);

        $this->assertFileExists($logPath);
        $contenido = file_get_contents($logPath);

        $this->assertStringNotContainsString($formTokenSecreto, $contenido);
        $this->assertStringNotContainsString($firmaInvalida, $contenido);
        $this->assertStringNotContainsString((string) config('izipay.password'), $contenido);
        $this->assertStringNotContainsString((string) config('izipay.sha256_key'), $contenido);

        @unlink($logPath);
    }

    public function test_headers_de_seguridad_presentes_en_productos(): void
    {
        $response = $this->get('/productos');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotNull($response->headers->get('Permissions-Policy'));

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString('*.micuentaweb.pe', $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);

        preg_match("/'nonce-([a-zA-Z0-9]+)'/", $csp, $m);
        $this->assertNotEmpty($m, 'El CSP debe declarar un nonce para script-src.');
        $response->assertSee('nonce="'.$m[1].'"', false);
    }

    public function test_comando_expira_pagos_pendientes_de_mas_de_24_horas(): void
    {
        // Izipay responde que no conoce la orden: el comprador abrió el
        // checkout y nunca llegó a intentar pagar.
        Http::fake([
            '*/V4/Order/Get' => Http::response([
                'status' => 'ERROR',
                'answer' => ['errorCode' => 'PSP_010'],
            ], 200),
        ]);

        $viejo = $this->crearPagoPendiente();
        $viejo->forceFill(['created_at' => now()->subDay()->subMinute()])->save();

        $reciente = $this->crearPagoPendiente();

        $this->artisan('izipay:expirar-pendientes')->assertSuccessful();

        $this->assertSame(EstadoPago::Expirado, $viejo->fresh()->estado);
        $this->assertSame(EstadoPago::Pendiente, $reciente->fresh()->estado);
    }

    public function test_el_comando_de_expiracion_rescata_un_pago_que_si_se_cobro(): void
    {
        $viejo = $this->crearPagoPendiente();
        $viejo->forceFill(['created_at' => now()->subDay()->subMinute()])->save();

        Http::fake([
            '*/V4/Order/Get' => Http::response([
                'status' => 'SUCCESS',
                'answer' => [
                    'orderStatus' => 'PAID',
                    'orderDetails' => [
                        'orderId' => $viejo->izipay_order_id,
                        'orderTotalAmount' => $viejo->monto,
                        'orderCurrency' => $viejo->moneda,
                    ],
                    'transactions' => [['uuid' => 'uuid-rescatado', 'detailedStatus' => 'CAPTURED']],
                ],
            ], 200),
        ]);

        $this->artisan('izipay:expirar-pendientes')->assertSuccessful();

        // Nunca se expira a ciegas: si Izipay dice que se cobró, se cobra.
        $this->assertSame(EstadoPago::Pagado, $viejo->fresh()->estado);
    }

    public function test_el_comando_de_expiracion_no_toca_nada_si_izipay_no_responde(): void
    {
        $viejo = $this->crearPagoPendiente();
        $viejo->forceFill(['created_at' => now()->subDay()->subMinute()])->save();

        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $this->artisan('izipay:expirar-pendientes')->assertSuccessful();

        $this->assertSame(EstadoPago::Pendiente, $viejo->fresh()->estado);
    }

    public function test_rate_limit_form_token_por_ip_y_email(): void
    {
        $this->fakeFormToken();
        $producto = $this->primerProducto();

        $payload = [
            'producto' => $producto['slug'],
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'email' => 'mismo@example.com',
            'phone_number' => '+51999999999',
            'identity_code' => '12345678',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('izipay.form-token'), $payload)->assertOk();
        }

        $this->postJson(route('izipay.form-token'), $payload)->assertStatus(429);
    }

    /** @return array<string, string> */
    private function firmarParaNavegador(array $answer): array
    {
        return array_merge($this->firmar($answer, config('izipay.sha256_key')), [
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'sha256_hmac',
        ]);
    }

    /** @return array<string, string> */
    private function firmarParaIpn(array $answer): array
    {
        return array_merge($this->firmar($answer, config('izipay.password')), [
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]);
    }

    private function answerDelPago(Pago $pago, array $overrides = []): array
    {
        return $this->krAnswer(array_replace_recursive([
            'orderDetails' => [
                'orderId' => $pago->izipay_order_id,
                'orderTotalAmount' => $pago->monto,
                'orderCurrency' => $pago->moneda,
            ],
        ], $overrides));
    }

    public function test_no_se_pueden_guardar_dos_pagos_con_el_mismo_transaction_uuid(): void
    {
        $this->crearPagoPendiente(['transaction_uuid' => 'uuid-repetido']);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        $this->crearPagoPendiente(['transaction_uuid' => 'uuid-repetido']);
    }

    public function test_varios_pagos_pendientes_pueden_tener_transaction_uuid_nulo(): void
    {
        $this->crearPagoPendiente();
        $this->crearPagoPendiente();

        $this->assertSame(2, Pago::whereNull('transaction_uuid')->count());
    }

    public function test_envia_la_ip_real_del_comprador_a_izipay(): void
    {
        $this->fakeFormToken();
        $producto = $this->primerProducto();

        // REMOTE_ADDR es la IP real; el X-Forwarded-For falsificado debe
        // ignorarse para no ensuciar el antifraude de Izipay.
        $this->withServerVariables(['REMOTE_ADDR' => '190.235.10.20'])
            ->withHeaders(['X-Forwarded-For' => '10.0.0.66'])
            ->postJson(route('izipay.form-token'), [
                'producto' => $producto['slug'],
                'first_name' => 'Juan',
                'last_name' => 'Perez',
                'email' => 'juan@example.com',
                'phone_number' => '+51999999999',
                'identity_code' => '12345678',
            ])->assertOk();

        Http::assertSent(function ($request) {
            return data_get($request->data(), 'customer.extraDetails.ipAddress') === '190.235.10.20';
        });
    }

    public function test_envia_nombre_telefono_y_documento_en_billing_details(): void
    {
        $this->fakeFormToken();
        $producto = $this->primerProducto();

        $this->postJson(route('izipay.form-token'), [
            'producto' => $producto['slug'],
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'email' => 'juan@example.com',
            'phone_number' => '+51999999999',
            'identity_code' => '12345678',
        ])->assertOk();

        Http::assertSent(function ($request) {
            $billing = data_get($request->data(), 'customer.billingDetails');

            return $billing['firstName'] === 'Juan'
                && $billing['lastName'] === 'Perez'
                && $billing['phoneNumber'] === '+51999999999'
                && $billing['identityType'] === 'DNI'
                && $billing['identityCode'] === '12345678'
                && $billing['country'] === 'PE';
        });
    }

    public function test_los_timeouts_tienen_tope_explicito_y_razonable(): void
    {
        // Sin tope, una caída lenta de Izipay deja colgado un worker de PHP.
        $conexion = config('izipay.connect_timeout');
        $total = config('izipay.timeout');

        $this->assertIsInt($conexion);
        $this->assertIsInt($total);
        $this->assertGreaterThan(0, $conexion);
        $this->assertLessThanOrEqual(10, $conexion);
        $this->assertLessThanOrEqual(30, $total);
        $this->assertGreaterThanOrEqual($conexion, $total);
    }

    public function test_si_izipay_no_responde_no_se_crea_el_pago(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));
        $producto = $this->primerProducto();

        $response = $this->postJson(route('izipay.form-token'), [
            'producto' => $producto['slug'],
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'email' => 'juan@example.com',
            'phone_number' => '+51999999999',
            'identity_code' => '12345678',
        ]);

        $response->assertStatus(422)->assertJson(['ok' => false]);
        $this->assertDatabaseMissing('pagos', ['producto' => $producto['slug']]);
    }

    public function test_el_retorno_del_navegador_nunca_marca_pagado(): void
    {
        $pago = $this->crearPagoPendiente();
        $answer = $this->answerDelPago($pago); // orderStatus PAID

        $response = $this->postJson(route('izipay.validar'), $this->firmarParaNavegador($answer));

        $response->assertStatus(202)->assertJson(['ok' => false, 'pendiente' => true]);
        $this->assertSame(EstadoPago::EnVerificacion, $pago->fresh()->estado);
    }

    public function test_el_ipn_si_marca_pagado(): void
    {
        $pago = $this->crearPagoPendiente();
        $answer = $this->answerDelPago($pago);

        $this->postJson(route('izipay.ipn'), $this->firmarParaIpn($answer))->assertOk();

        $this->assertSame(EstadoPago::Pagado, $pago->fresh()->estado);
    }

    public function test_el_ipn_confirma_un_pago_que_el_navegador_dejo_en_verificacion(): void
    {
        $pago = $this->crearPagoPendiente();
        $answer = $this->answerDelPago($pago);

        $this->postJson(route('izipay.validar'), $this->firmarParaNavegador($answer))->assertStatus(202);
        $this->assertSame(EstadoPago::EnVerificacion, $pago->fresh()->estado);

        $this->postJson(route('izipay.ipn'), $this->firmarParaIpn($answer))->assertOk();
        $this->assertSame(EstadoPago::Pagado, $pago->fresh()->estado);
    }

    public function test_ipn_pendiente_de_autorizacion_no_marca_pagado(): void
    {
        $pago = $this->crearPagoPendiente();
        $answer = $this->answerDelPago($pago, [
            'orderStatus' => 'RUNNING',
            'transactions' => [['detailedStatus' => 'WAITING_AUTHORISATION']],
        ]);

        $this->postJson(route('izipay.ipn'), $this->firmarParaIpn($answer))->assertOk();

        $this->assertSame(EstadoPago::EnVerificacion, $pago->fresh()->estado);
    }

    public function test_ipn_autorizado_sin_capturar_no_marca_pagado(): void
    {
        // Caso real de producción (24/09): una autorización sin capturar se
        // marcó "pagado" y horas después se anuló en Izipay sin que nuestro
        // sistema se enterara, porque 'pagado' es inmutable. AUTHORISED sin
        // captura debe quedar en verificación, no pagado.
        $pago = $this->crearPagoPendiente();
        $answer = $this->answerDelPago($pago, [
            'transactions' => [['detailedStatus' => 'AUTHORISED']],
        ]);

        $this->postJson(route('izipay.ipn'), $this->firmarParaIpn($answer))->assertOk();

        $this->assertSame(EstadoPago::EnVerificacion, $pago->fresh()->estado);
    }

    public function test_ipn_capturado_si_marca_pagado(): void
    {
        $pago = $this->crearPagoPendiente();
        $answer = $this->answerDelPago($pago, [
            'transactions' => [['detailedStatus' => 'CAPTURED']],
        ]);

        $this->postJson(route('izipay.ipn'), $this->firmarParaIpn($answer))->assertOk();

        $this->assertSame(EstadoPago::Pagado, $pago->fresh()->estado);
    }

    public function test_ipn_con_captura_fallida_marca_rechazado(): void
    {
        $pago = $this->crearPagoPendiente();
        $answer = $this->answerDelPago($pago, [
            'transactions' => [['detailedStatus' => 'CAPTURE_FAILED']],
        ]);

        $this->postJson(route('izipay.ipn'), $this->firmarParaIpn($answer))->assertOk();

        $this->assertSame(EstadoPago::Rechazado, $pago->fresh()->estado);
    }

    public function test_los_mensajes_al_cliente_no_prometen_correo(): void
    {
        $pago = $this->crearPagoPendiente();
        $answer = $this->answerDelPago($pago);

        $respuestaNavegador = $this->postJson(route('izipay.validar'), $this->firmarParaNavegador($answer));
        $this->assertStringNotContainsStringIgnoringCase('correo', $respuestaNavegador->json('mensaje'));

        $this->postJson(route('izipay.ipn'), $this->firmarParaIpn($answer))->assertOk();

        $respuestaFinal = $this->postJson(route('izipay.validar'), $this->firmarParaNavegador($answer));
        $this->assertStringNotContainsStringIgnoringCase('correo', $respuestaFinal->json('mensaje'));
    }
}
