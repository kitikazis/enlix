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

class IzipayConciliacionTest extends TestCase
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

    private function primerProducto(): array
    {
        return Producto::find(array_key_first(Producto::items()));
    }

    private function crearPago(EstadoPago $estado, int $minutosAtras): Pago
    {
        $producto = $this->primerProducto();

        $pago = Pago::create([
            'producto' => $producto['slug'],
            'email' => 'cliente@example.com',
            'monto' => $producto['precio_centimos'],
            'moneda' => 'PEN',
            'izipay_order_id' => 'ENX-CONC-'.uniqid(),
            'estado' => $estado,
        ]);

        $pago->forceFill(['created_at' => now()->subMinutes($minutosAtras)])->save();

        return $pago->fresh();
    }

    private function fakeOrderGet(Pago $pago, string $orderStatus, ?string $detailedStatus): void
    {
        Http::fake([
            '*/V4/Order/Get' => Http::response([
                'status' => 'SUCCESS',
                'answer' => [
                    'orderStatus' => $orderStatus,
                    'orderDetails' => [
                        'orderId' => $pago->izipay_order_id,
                        'orderTotalAmount' => $pago->monto,
                        'orderCurrency' => $pago->moneda,
                    ],
                    'transactions' => [[
                        'uuid' => 'uuid-'.uniqid(),
                        'detailedStatus' => $detailedStatus,
                    ]],
                ],
            ], 200),
        ]);
    }

    public function test_recupera_un_pago_cobrado_cuya_ipn_nunca_llego(): void
    {
        $pago = $this->crearPago(EstadoPago::Pendiente, 45);
        $this->fakeOrderGet($pago, 'PAID', 'CAPTURED');

        $this->artisan('izipay:conciliar-pendientes')->assertSuccessful();

        $this->assertSame(EstadoPago::Pagado, $pago->fresh()->estado);
    }

    public function test_marca_rechazado_lo_que_izipay_reporta_como_rechazado(): void
    {
        $pago = $this->crearPago(EstadoPago::EnVerificacion, 45);
        $this->fakeOrderGet($pago, 'UNPAID', 'REFUSED');

        $this->artisan('izipay:conciliar-pendientes')->assertSuccessful();

        $this->assertSame(EstadoPago::Rechazado, $pago->fresh()->estado);
    }

    public function test_si_izipay_no_responde_no_toca_el_pago(): void
    {
        $pago = $this->crearPago(EstadoPago::Pendiente, 45);
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $this->artisan('izipay:conciliar-pendientes')->assertSuccessful();

        $this->assertSame(EstadoPago::Pendiente, $pago->fresh()->estado);
    }

    public function test_si_izipay_no_conoce_la_orden_no_la_toca(): void
    {
        $pago = $this->crearPago(EstadoPago::Pendiente, 45);
        Http::fake([
            '*/V4/Order/Get' => Http::response([
                'status' => 'ERROR',
                'answer' => ['errorCode' => 'PSP_010'],
            ], 200),
        ]);

        $this->artisan('izipay:conciliar-pendientes')->assertSuccessful();

        // La expiración (a las 24h) es la que cierra este caso, no la conciliación.
        $this->assertSame(EstadoPago::Pendiente, $pago->fresh()->estado);
    }

    public function test_no_revisa_pagos_demasiado_recientes(): void
    {
        $pago = $this->crearPago(EstadoPago::Pendiente, 5);
        $this->fakeOrderGet($pago, 'PAID', 'CAPTURED');

        $this->artisan('izipay:conciliar-pendientes')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(EstadoPago::Pendiente, $pago->fresh()->estado);
    }

    public function test_no_revisa_pendientes_de_mas_de_24_horas(): void
    {
        $pago = $this->crearPago(EstadoPago::Pendiente, 60 * 25);
        $this->fakeOrderGet($pago, 'PAID', 'CAPTURED');

        $this->artisan('izipay:conciliar-pendientes')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_revisa_en_verificacion_hasta_72_horas(): void
    {
        $pago = $this->crearPago(EstadoPago::EnVerificacion, 60 * 48);
        $this->fakeOrderGet($pago, 'PAID', 'CAPTURED');

        $this->artisan('izipay:conciliar-pendientes')->assertSuccessful();

        $this->assertSame(EstadoPago::Pagado, $pago->fresh()->estado);
    }

    public function test_no_pisa_el_resultado_de_una_ipn_que_ya_cerro_el_pago(): void
    {
        // Carrera: la IPN llegó primero y dejó el pago rechazado; la
        // conciliación no puede revivirlo aunque Izipay ahora diga PAID.
        $pago = $this->crearPago(EstadoPago::Rechazado, 45);
        $this->fakeOrderGet($pago, 'PAID', 'CAPTURED');

        $this->artisan('izipay:conciliar-pendientes')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(EstadoPago::Rechazado, $pago->fresh()->estado);
    }

    public function test_no_marca_pagado_si_el_monto_no_coincide(): void
    {
        $pago = $this->crearPago(EstadoPago::Pendiente, 45);

        Http::fake([
            '*/V4/Order/Get' => Http::response([
                'status' => 'SUCCESS',
                'answer' => [
                    'orderStatus' => 'PAID',
                    'orderDetails' => [
                        'orderId' => $pago->izipay_order_id,
                        'orderTotalAmount' => $pago->monto + 5000,
                        'orderCurrency' => $pago->moneda,
                    ],
                    'transactions' => [['uuid' => 'uuid-x', 'detailedStatus' => 'CAPTURED']],
                ],
            ], 200),
        ]);

        $this->artisan('izipay:conciliar-pendientes')->assertSuccessful();

        $this->assertSame(EstadoPago::Pendiente, $pago->fresh()->estado);
    }
}
