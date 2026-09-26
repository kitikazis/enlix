<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EstadoPago;
use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Antes de izipay:conciliar-pedidos-pendientes, un pedido que quedaba en
 * 'en_verificacion' (autorizado en el navegador) no tenia ningun mecanismo
 * de rescate si la IPN de captura se perdia: pedidos:liberar-reservas-expiradas
 * solo revisa 'pendiente'. Estos tests cubren ese comando nuevo, en espejo a
 * IzipayConciliacionTest (mismo comportamiento, tabla `pedidos`).
 */
class IzipayConciliacionPedidosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'izipay.username' => 'test-user',
            'izipay.password' => 'test-password-secreta',
            'izipay.currency' => 'PEN',
        ]);
    }

    private function crearPedido(EstadoPago $estado, int $minutosAtras): Pedido
    {
        $pedido = Pedido::create([
            'codigo' => 'ENX-CONC-'.uniqid(),
            'nombre_cliente' => 'Juan Perez',
            'email' => 'cliente@example.com',
            'telefono' => '+51999999999',
            'tipo_documento' => 'DNI',
            'numero_documento' => '12345678',
            'tipo_comprobante' => 'boleta',
            'metodo_entrega' => 'recojo',
            'subtotal_centimos' => 150000,
            'total_centimos' => 150000,
            'estado_pago' => $estado,
            'izipay_order_id' => 'ENX-CONC-'.uniqid(),
        ]);

        $pedido->forceFill(['created_at' => now()->subMinutes($minutosAtras)])->save();

        return $pedido->fresh();
    }

    private function fakeOrderGet(Pedido $pedido, string $orderStatus, ?string $detailedStatus): void
    {
        Http::fake([
            '*/V4/Order/Get' => Http::response([
                'status' => 'SUCCESS',
                'answer' => [
                    'orderStatus' => $orderStatus,
                    'orderDetails' => [
                        'orderId' => $pedido->izipay_order_id,
                        'orderTotalAmount' => $pedido->total_centimos,
                        'orderCurrency' => 'PEN',
                    ],
                    'transactions' => [[
                        'uuid' => 'uuid-'.uniqid(),
                        'detailedStatus' => $detailedStatus,
                    ]],
                ],
            ], 200),
        ]);
    }

    public function test_recupera_un_pedido_capturado_cuya_ipn_nunca_llego(): void
    {
        $pedido = $this->crearPedido(EstadoPago::EnVerificacion, 45);
        $this->fakeOrderGet($pedido, 'PAID', 'CAPTURED');

        $this->artisan('izipay:conciliar-pedidos-pendientes')->assertSuccessful();

        $this->assertSame(EstadoPago::Pagado, $pedido->fresh()->estado_pago);
    }

    public function test_marca_rechazado_lo_que_izipay_reporta_como_rechazado(): void
    {
        $pedido = $this->crearPedido(EstadoPago::EnVerificacion, 45);
        $this->fakeOrderGet($pedido, 'UNPAID', 'REFUSED');

        $this->artisan('izipay:conciliar-pedidos-pendientes')->assertSuccessful();

        $this->assertSame(EstadoPago::Rechazado, $pedido->fresh()->estado_pago);
    }

    public function test_no_revisa_pedidos_pendientes_solo_en_verificacion(): void
    {
        // 'pendiente' lo cubre pedidos:liberar-reservas-expiradas (20 min);
        // este comando solo mira 'en_verificacion'.
        $pedido = $this->crearPedido(EstadoPago::Pendiente, 45);
        $this->fakeOrderGet($pedido, 'PAID', 'CAPTURED');

        $this->artisan('izipay:conciliar-pedidos-pendientes')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(EstadoPago::Pendiente, $pedido->fresh()->estado_pago);
    }

    public function test_no_revisa_pedidos_demasiado_recientes(): void
    {
        $pedido = $this->crearPedido(EstadoPago::EnVerificacion, 1);
        $this->fakeOrderGet($pedido, 'PAID', 'CAPTURED');

        $this->artisan('izipay:conciliar-pedidos-pendientes')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(EstadoPago::EnVerificacion, $pedido->fresh()->estado_pago);
    }

    public function test_no_revisa_en_verificacion_de_mas_de_72_horas(): void
    {
        $pedido = $this->crearPedido(EstadoPago::EnVerificacion, 60 * 73);
        $this->fakeOrderGet($pedido, 'PAID', 'CAPTURED');

        $this->artisan('izipay:conciliar-pedidos-pendientes')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_si_izipay_no_responde_no_toca_el_pedido(): void
    {
        $pedido = $this->crearPedido(EstadoPago::EnVerificacion, 45);
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $this->artisan('izipay:conciliar-pedidos-pendientes')->assertSuccessful();

        $this->assertSame(EstadoPago::EnVerificacion, $pedido->fresh()->estado_pago);
    }

    public function test_no_pisa_el_resultado_de_una_ipn_que_ya_cerro_el_pedido(): void
    {
        $pedido = $this->crearPedido(EstadoPago::Rechazado, 45);
        $this->fakeOrderGet($pedido, 'PAID', 'CAPTURED');

        $this->artisan('izipay:conciliar-pedidos-pendientes')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(EstadoPago::Rechazado, $pedido->fresh()->estado_pago);
    }
}
