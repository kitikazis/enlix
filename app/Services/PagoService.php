<?php

namespace App\Services;

use App\Models\Pago;
use App\Support\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Registra el resultado de un pago Izipay (kr-answer ya validado en firma).
 *
 * Reglas de seguridad (no relajar sin revisar de nuevo el checklist):
 * - NUNCA crea una fila `pagos`: solo la actualiza. La fila 'pendiente' la
 *   crea unicamente IzipayController::formToken(). Si esta clase creara
 *   filas, un kr-answer falsificado posteado directo a /izipay/validar
 *   podria fabricar un pago 'pagado' para un order_id inventado.
 * - Un pago 'pagado' nunca cambia de estado desde aqui (idempotente).
 * - Se valida moneda Y monto contra lo que se registro en el formToken,
 *   no solo la moneda.
 */
class PagoService
{
    public function registrar(array $answer, string $origen): array
    {
        $orderId = data_get($answer, 'orderDetails.orderId');

        if (! $orderId) {
            Log::warning('Izipay: kr-answer sin orderId', ['origen' => $origen]);

            return ['ok' => true, 'procesado' => false, 'motivo' => 'sin_order_id'];
        }

        return DB::transaction(function () use ($answer, $orderId, $origen) {
            $pago = Pago::where('izipay_order_id', $orderId)->lockForUpdate()->first();

            if ($pago === null) {
                // No se crea la fila aqui a proposito (ver docblock de la clase).
                Log::info('Izipay: order_id no reconocido', ['order_id' => $orderId, 'origen' => $origen]);

                return ['ok' => true, 'procesado' => false, 'motivo' => 'order_id_desconocido'];
            }

            if ($pago->estado === 'pagado') {
                // Idempotente: ya esta pagado, no se toca sin importar lo que diga este answer.
                return ['ok' => true, 'procesado' => true, 'estado' => 'pagado'];
            }

            $monedaOk = data_get($answer, 'orderDetails.orderCurrency') === $pago->moneda;
            $montoOk = (int) data_get($answer, 'orderDetails.orderTotalAmount', -1) === (int) $pago->monto;
            $productoOk = Producto::find($pago->producto) !== null;

            if (! $monedaOk || ! $montoOk || ! $productoOk) {
                Log::warning('Izipay: kr-answer no coincide con el pago registrado', [
                    'order_id' => $orderId,
                    'moneda_ok' => $monedaOk,
                    'monto_ok' => $montoOk,
                    'producto_ok' => $productoOk,
                ]);

                return ['ok' => false, 'procesado' => false, 'motivo' => 'mismatch'];
            }

            $orderStatus = data_get($answer, 'orderStatus');
            $nuevoEstado = $orderStatus === 'PAID' ? 'pagado' : 'rechazado';

            $pago->estado = $nuevoEstado;
            $pago->izipay_order_id = $orderId;
            $pago->transaction_uuid = data_get($answer, 'transactions.0.uuid');
            $pago->card_brand = data_get($answer, 'transactions.0.transactionDetails.cardDetails.effectiveBrand');
            $pago->card_masked_pan = data_get($answer, 'transactions.0.transactionDetails.cardDetails.pan');
            $pago->respuesta = $this->sanitizarRespuesta($answer);
            $pago->save();

            Log::info('Izipay: pago registrado', ['order_id' => $orderId, 'estado' => $nuevoEstado]);

            return ['ok' => true, 'procesado' => true, 'estado' => $nuevoEstado];
        });
    }

    /**
     * Reduce el kr-answer completo al subconjunto minimo necesario.
     * El PAN que entrega Izipay ya viene enmascarado (nunca el numero completo).
     */
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
