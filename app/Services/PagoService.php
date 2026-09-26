<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoPago;
use App\Enums\MetodoPago;
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
 * - El retorno del navegador NUNCA marca pagado: como mucho deja el pago en
 *   verificacion. Solo el IPN (servidor a servidor) confirma el cobro.
 * - Un estado final no se mueve nunca mas (ver EstadoPago::puedeTransicionarA).
 * - Se valida moneda Y monto contra lo que se registro en el formToken.
 */
class PagoService
{
    public const ORIGEN_IPN = 'ipn';

    public const ORIGEN_VALIDAR = 'validar';

    public const ORIGEN_CONCILIACION = 'conciliacion';

    public const MOTIVO_SIN_ORDER_ID = 'sin_order_id';

    public const MOTIVO_ORDEN_DESCONOCIDA = 'order_id_desconocido';

    public const MOTIVO_NO_COINCIDE = 'mismatch';

    public const MOTIVO_TRANSICION_INVALIDA = 'transicion_invalida';

    /**
     * @return array{ok: bool, procesado: bool, estado?: EstadoPago, motivo?: string}
     */
    public function registrar(array $answer, string $origen): array
    {
        $orderId = data_get($answer, 'orderDetails.orderId');

        if (! is_string($orderId) || $orderId === '') {
            Log::warning('Izipay: kr-answer sin orderId', ['origen' => $origen]);

            return ['ok' => true, 'procesado' => false, 'motivo' => self::MOTIVO_SIN_ORDER_ID];
        }

        return DB::transaction(function () use ($answer, $orderId, $origen): array {
            $pago = Pago::where('izipay_order_id', $orderId)->lockForUpdate()->first();

            if ($pago === null) {
                // No se crea la fila aqui a proposito (ver docblock de la clase).
                Log::info('Izipay: order_id no reconocido', [
                    'izipay_order_id' => $orderId,
                    'origen' => $origen,
                ]);

                return ['ok' => true, 'procesado' => false, 'motivo' => self::MOTIVO_ORDEN_DESCONOCIDA];
            }

            $estadoActual = $pago->estado;

            if ($estadoActual->esFinal()) {
                // Idempotente: lo que diga este answer ya no cambia nada.
                return ['ok' => true, 'procesado' => true, 'estado' => $estadoActual];
            }

            if (! $this->coincideConLaOrden($answer, $pago)) {
                return ['ok' => false, 'procesado' => false, 'motivo' => self::MOTIVO_NO_COINCIDE];
            }

            $estadoNuevo = $this->estadoSegunOrigen($answer, $origen);

            if (! $estadoActual->puedeTransicionarA($estadoNuevo)) {
                Log::info('Izipay: transicion de estado ignorada', [
                    'pago_id' => $pago->id,
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

            $this->aplicarEstado($pago, $estadoNuevo, $answer);

            Log::info('Izipay: pago actualizado', [
                'pago_id' => $pago->id,
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
     * la respuesta dice "pagado", aqui se topa en verificacion y se espera la
     * confirmacion del IPN, que es la fuente de verdad.
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

    private function coincideConLaOrden(array $answer, Pago $pago): bool
    {
        $monedaOk = data_get($answer, 'orderDetails.orderCurrency') === $pago->moneda;
        $montoOk = (int) data_get($answer, 'orderDetails.orderTotalAmount', -1) === $pago->monto;
        $productoOk = Producto::find($pago->producto) !== null;

        if ($monedaOk && $montoOk && $productoOk) {
            return true;
        }

        Log::warning('Izipay: la respuesta no coincide con el pago registrado', [
            'pago_id' => $pago->id,
            'izipay_order_id' => $pago->izipay_order_id,
            'moneda_ok' => $monedaOk,
            'monto_ok' => $montoOk,
            'producto_ok' => $productoOk,
        ]);

        return false;
    }

    private function aplicarEstado(Pago $pago, EstadoPago $estado, array $answer): void
    {
        $pago->estado = $estado;
        $pago->transaction_uuid = data_get($answer, 'transactions.0.uuid');
        $pago->card_brand = data_get($answer, 'transactions.0.transactionDetails.cardDetails.effectiveBrand');
        $pago->card_masked_pan = data_get($answer, 'transactions.0.transactionDetails.cardDetails.pan');
        $pago->metodo_pago = MetodoPago::desdeRespuestaIzipay($answer);
        $pago->respuesta = $this->sanitizarRespuesta($answer);
        $pago->save();
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
