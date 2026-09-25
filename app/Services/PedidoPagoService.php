<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoPago;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Registra el resultado de un pago Izipay de un pedido (kr-answer ya
 * validado en firma). Mismas reglas que PagoService, aplicadas a `pedidos`
 * en vez de `pagos` - ver su docblock para el porqué de cada una:
 * - NUNCA crea una fila `pedidos`: solo la actualiza (la crea PedidoService).
 * - El retorno del navegador NUNCA marca pagado, solo "en verificación".
 * - Un estado final no se mueve nunca más.
 * - Se valida moneda Y monto contra lo que se registró al crear el pedido.
 *
 * Además de actualizar el estado, mueve el stock: confirma (sale del
 * inventario físico) si quedó pagado, libera la reserva si quedó
 * rechazado/anulado/expirado.
 */
class PedidoPagoService
{
    public const ORIGEN_IPN = 'ipn';

    public const ORIGEN_VALIDAR = 'validar';

    public const ORIGEN_CONCILIACION = 'conciliacion';

    public const MOTIVO_SIN_ORDER_ID = 'sin_order_id';

    public const MOTIVO_ORDEN_DESCONOCIDA = 'order_id_desconocido';

    public const MOTIVO_NO_COINCIDE = 'mismatch';

    public const MOTIVO_TRANSICION_INVALIDA = 'transicion_invalida';

    private const ESTADOS_QUE_LIBERAN_STOCK = [
        EstadoPago::Rechazado,
        EstadoPago::Anulado,
        EstadoPago::Expirado,
    ];

    public function __construct(private readonly StockService $stock) {}

    /**
     * @return array{ok: bool, procesado: bool, estado?: EstadoPago, motivo?: string}
     */
    public function registrar(array $answer, string $origen): array
    {
        $orderId = data_get($answer, 'orderDetails.orderId');

        if (! is_string($orderId) || $orderId === '') {
            Log::warning('Izipay pedidos: kr-answer sin orderId', ['origen' => $origen]);

            return ['ok' => true, 'procesado' => false, 'motivo' => self::MOTIVO_SIN_ORDER_ID];
        }

        return DB::transaction(function () use ($answer, $orderId, $origen): array {
            $pedido = Pedido::where('izipay_order_id', $orderId)->lockForUpdate()->first();

            if ($pedido === null) {
                Log::info('Izipay pedidos: order_id no reconocido', [
                    'izipay_order_id' => $orderId,
                    'origen' => $origen,
                ]);

                return ['ok' => true, 'procesado' => false, 'motivo' => self::MOTIVO_ORDEN_DESCONOCIDA];
            }

            $estadoActual = $pedido->estado_pago;

            if ($estadoActual->esFinal()) {
                // Idempotente: lo que diga este answer ya no cambia nada.
                return ['ok' => true, 'procesado' => true, 'estado' => $estadoActual];
            }

            if (! $this->coincideConElPedido($answer, $pedido)) {
                return ['ok' => false, 'procesado' => false, 'motivo' => self::MOTIVO_NO_COINCIDE];
            }

            $estadoNuevo = $this->estadoSegunOrigen($answer, $origen);

            if (! $estadoActual->puedeTransicionarA($estadoNuevo)) {
                Log::info('Izipay pedidos: transicion de estado ignorada', [
                    'pedido_id' => $pedido->id,
                    'izipay_order_id' => $orderId,
                    'origen' => $origen,
                    'estado_anterior' => $estadoActual->value,
                    'estado_nuevo' => $estadoNuevo->value,
                ]);

                return [
                    'ok' => true,
                    'procesado' => true,
                    'estado' => $estadoActual,
                    'motivo' => self::MOTIVO_TRANSICION_INVALIDA,
                ];
            }

            $this->aplicarEstado($pedido, $estadoNuevo, $answer);

            Log::info('Izipay pedidos: pedido actualizado', [
                'pedido_id' => $pedido->id,
                'izipay_order_id' => $orderId,
                'origen' => $origen,
                'estado_anterior' => $estadoActual->value,
                'estado_nuevo' => $estadoNuevo->value,
            ]);

            return ['ok' => true, 'procesado' => true, 'estado' => $estadoNuevo];
        });
    }

    /**
     * El retorno del navegador solo sirve para la experiencia de usuario: si
     * la respuesta dice "pagado", aquí se topa en verificación y se espera
     * la confirmación del IPN, que es la fuente de verdad.
     */
    private function estadoSegunOrigen(array $answer, string $origen): EstadoPago
    {
        $estado = EstadoPago::desdeRespuestaIzipay(
            data_get($answer, 'orderStatus'),
            data_get($answer, 'transactions.0.detailedStatus'),
        );

        if ($origen === self::ORIGEN_VALIDAR && $estado === EstadoPago::Pagado) {
            return EstadoPago::EnVerificacion;
        }

        return $estado;
    }

    private function coincideConElPedido(array $answer, Pedido $pedido): bool
    {
        $monedaOk = data_get($answer, 'orderDetails.orderCurrency') === config('izipay.currency', 'PEN');
        $montoOk = (int) data_get($answer, 'orderDetails.orderTotalAmount', -1) === $pedido->total_centimos;

        if ($monedaOk && $montoOk) {
            return true;
        }

        Log::warning('Izipay pedidos: la respuesta no coincide con el pedido registrado', [
            'pedido_id' => $pedido->id,
            'izipay_order_id' => $pedido->izipay_order_id,
            'moneda_ok' => $monedaOk,
            'monto_ok' => $montoOk,
        ]);

        return false;
    }

    private function aplicarEstado(Pedido $pedido, EstadoPago $estado, array $answer): void
    {
        $estadoAnterior = $pedido->estado_pago;

        $pedido->estado_pago = $estado;
        $pedido->transaction_uuid = data_get($answer, 'transactions.0.uuid');
        $pedido->card_brand = data_get($answer, 'transactions.0.transactionDetails.cardDetails.effectiveBrand');
        $pedido->card_masked_pan = data_get($answer, 'transactions.0.transactionDetails.cardDetails.pan');
        $pedido->respuesta = $this->sanitizarRespuesta($answer);

        if ($estado === EstadoPago::Pagado) {
            $pedido->pagado_en = now();
            $pedido->estado_envio = EstadoEnvio::Pendiente;
        }

        $pedido->save();

        if ($estado === EstadoPago::Pagado && $estadoAnterior !== EstadoPago::Pagado) {
            $this->moverStock($pedido, fn ($producto, $cantidad) => $this->stock->confirmar($producto, $cantidad, $pedido->codigo));
        }

        if (in_array($estado, self::ESTADOS_QUE_LIBERAN_STOCK, true)) {
            $this->moverStock($pedido, fn ($producto, $cantidad) => $this->stock->liberar($producto, $cantidad, $pedido->codigo));
        }
    }

    private function moverStock(Pedido $pedido, \Closure $accion): void
    {
        foreach ($pedido->items as $item) {
            if ($item->producto !== null) {
                $accion($item->producto, $item->cantidad);
            }
        }
    }

    /** Reduce el kr-answer completo al subconjunto mínimo necesario (mismo criterio que PagoService). */
    private function sanitizarRespuesta(array $answer): array
    {
        return [
            'orderStatus' => data_get($answer, 'orderStatus'),
            'orderDetails' => [
                'orderId' => data_get($answer, 'orderDetails.orderId'),
                'orderTotalAmount' => data_get($answer, 'orderDetails.orderTotalAmount'),
                'orderCurrency' => data_get($answer, 'orderDetails.orderCurrency'),
            ],
            'transaction' => [
                'uuid' => data_get($answer, 'transactions.0.uuid'),
                'status' => data_get($answer, 'transactions.0.status'),
                'detailedStatus' => data_get($answer, 'transactions.0.detailedStatus'),
                'brand' => data_get($answer, 'transactions.0.transactionDetails.cardDetails.effectiveBrand'),
                'pan' => data_get($answer, 'transactions.0.transactionDetails.cardDetails.pan'),
            ],
        ];
    }
}
