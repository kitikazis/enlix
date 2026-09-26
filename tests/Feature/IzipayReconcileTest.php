<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EstadoPago;
use App\Models\Pago;
use App\Models\Pedido;
use App\Support\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * izipay:reconcile es la herramienta manual (--order puntual o --days de
 * barrido) que complementa a los jobs automaticos de ventana acotada
 * (izipay:conciliar-pendientes, izipay:conciliar-pedidos-pendientes). Cubre
 * tanto `pagos` (flujo viejo) como `pedidos` (checkout del carrito).
 */
class IzipayReconcileTest extends TestCase
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

    private function crearPago(EstadoPago $estado, int $minutosAtras, string $orderId): Pago
    {
        $producto = Producto::find(array_key_first(Producto::items()));

        $pago = Pago::create([
            'producto' => $producto['slug'],
            'email' => 'cliente@example.com',
            'monto' => $producto['precio_centimos'],
            'moneda' => 'PEN',
            'izipay_order_id' => $orderId,
            'estado' => $estado,
        ]);

        $pago->forceFill(['created_at' => now()->subMinutes($minutosAtras)])->save();

        return $pago->fresh();
    }

    private function crearPedido(EstadoPago $estado, int $minutosAtras, string $orderId): Pedido
    {
        $pedido = Pedido::create([
            'codigo' => $orderId,
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
            'izipay_order_id' => $orderId,
        ]);

        $pedido->forceFill(['created_at' => now()->subMinutes($minutosAtras)])->save();

        return $pedido->fresh();
    }

    private function fakeOrderGet(string $orderId, int $montoCentimos, string $orderStatus, ?string $detailedStatus): void
    {
        Http::fake([
            '*/V4/Order/Get' => Http::response([
                'status' => 'SUCCESS',
                'answer' => [
                    'orderStatus' => $orderStatus,
                    'orderDetails' => [
                        'orderId' => $orderId,
                        'orderTotalAmount' => $montoCentimos,
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

    public function test_order_puntual_resuelve_un_pago(): void
    {
        $pago = $this->crearPago(EstadoPago::Pendiente, 45, 'ENX-RECON-PAGO-1');
        $this->fakeOrderGet('ENX-RECON-PAGO-1', $pago->monto, 'PAID', 'CAPTURED');

        $this->artisan('izipay:reconcile', ['--order' => 'ENX-RECON-PAGO-1'])->assertSuccessful();

        $this->assertSame(EstadoPago::Pagado, $pago->fresh()->estado);
    }

    public function test_order_puntual_resuelve_un_pedido(): void
    {
        $pedido = $this->crearPedido(EstadoPago::EnVerificacion, 45, 'ENX-RECON-PED-1');
        $this->fakeOrderGet('ENX-RECON-PED-1', $pedido->total_centimos, 'PAID', 'CAPTURED');

        $this->artisan('izipay:reconcile', ['--order' => 'ENX-RECON-PED-1'])->assertSuccessful();

        $this->assertSame(EstadoPago::Pagado, $pedido->fresh()->estado_pago);
    }

    public function test_order_puntual_inexistente_falla_sin_reventar(): void
    {
        $this->artisan('izipay:reconcile', ['--order' => 'ENX-NO-EXISTE'])
            ->assertFailed();
    }

    public function test_barrido_por_dias_resuelve_pagos_y_pedidos_no_finales(): void
    {
        $pago = $this->crearPago(EstadoPago::Pendiente, 45, 'ENX-BARRIDO-PAGO');
        $pedido = $this->crearPedido(EstadoPago::EnVerificacion, 45, 'ENX-BARRIDO-PED');

        Http::fake([
            '*/V4/Order/Get' => function ($request) use ($pago, $pedido) {
                $body = json_decode($request->body(), true);
                $orderId = $body['orderId'] ?? null;
                $monto = $orderId === $pago->izipay_order_id ? $pago->monto : $pedido->total_centimos;

                return Http::response([
                    'status' => 'SUCCESS',
                    'answer' => [
                        'orderStatus' => 'PAID',
                        'orderDetails' => [
                            'orderId' => $orderId,
                            'orderTotalAmount' => $monto,
                            'orderCurrency' => 'PEN',
                        ],
                        'transactions' => [['uuid' => 'uuid-'.uniqid(), 'detailedStatus' => 'CAPTURED']],
                    ],
                ], 200);
            },
        ]);

        $this->artisan('izipay:reconcile', ['--days' => 7])->assertSuccessful();

        $this->assertSame(EstadoPago::Pagado, $pago->fresh()->estado);
        $this->assertSame(EstadoPago::Pagado, $pedido->fresh()->estado_pago);
    }

    public function test_barrido_no_toca_lo_que_ya_esta_en_estado_final(): void
    {
        $pago = $this->crearPago(EstadoPago::Pagado, 45, 'ENX-BARRIDO-FINAL');

        $this->artisan('izipay:reconcile', ['--days' => 7])->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(EstadoPago::Pagado, $pago->fresh()->estado);
    }

    public function test_barrido_respeta_la_ventana_de_dias(): void
    {
        $pago = $this->crearPago(EstadoPago::Pendiente, 60 * 24 * 10, 'ENX-BARRIDO-VIEJO');

        $this->artisan('izipay:reconcile', ['--days' => 7])->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(EstadoPago::Pendiente, $pago->fresh()->estado);
    }
}
