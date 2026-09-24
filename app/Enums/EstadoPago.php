<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Facades\Log;

/**
 * Estados de un pago. Los valores coinciden con los que ya existen en la
 * columna `pagos.estado`, así que no requieren migración de datos.
 *
 * El mapeo desde la respuesta de Izipay vive SOLO aquí
 * (desdeRespuestaIzipay): lo usan el IPN, el retorno del navegador, el
 * comando de expiración y el de conciliación.
 */
enum EstadoPago: string
{
    case Pendiente = 'pendiente';
    case EnVerificacion = 'en_verificacion';
    case Pagado = 'pagado';
    case Rechazado = 'rechazado';
    case Expirado = 'expirado';

    /** detailedStatus que descartan el pago de forma definitiva. */
    private const DETALLES_RECHAZO = ['REFUSED', 'CANCELLED', 'CAPTURE_FAILED'];

    /** detailedStatus que significan "caducó sin completarse". */
    private const DETALLES_EXPIRACION = ['EXPIRED', 'ABANDONED'];

    /** detailedStatus que aún no son definitivos: hay que volver a consultar. */
    private const DETALLES_VERIFICACION = [
        'AUTHORISED_TO_VALIDATE',
        'WAITING_AUTHORISATION',
        'WAITING_AUTHORISATION_TO_VALIDATE',
        'UNDER_VERIFICATION',
        'RUNNING',
        'INITIAL',
    ];

    public function esFinal(): bool
    {
        return match ($this) {
            self::Pagado, self::Rechazado, self::Expirado => true,
            self::Pendiente, self::EnVerificacion => false,
        };
    }

    /**
     * Un estado final no se mueve nunca más (un pago cobrado no puede
     * "des-cobrarse" por una notificación posterior), y no se retrocede de
     * EnVerificacion a Pendiente.
     */
    public function puedeTransicionarA(self $nuevo): bool
    {
        if ($this === $nuevo) {
            return false;
        }

        if ($this->esFinal()) {
            return false;
        }

        if ($this === self::EnVerificacion && $nuevo === self::Pendiente) {
            return false;
        }

        return true;
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::EnVerificacion => 'En verificación',
            self::Pagado => 'Pagado',
            self::Rechazado => 'Rechazado',
            self::Expirado => 'Expirado',
        };
    }

    /**
     * Traduce la respuesta de Izipay (orderStatus + detailedStatus de la
     * transacción) al estado interno.
     *
     * El detailedStatus manda cuando dice algo concluyente en contra o
     * cuando indica que el pago sigue en curso; si no, decide orderStatus.
     * Ante algo desconocido nunca se asume pagado: queda en verificación
     * para que el job de conciliación lo vuelva a consultar.
     *
     * // TODO: verificar la lista completa de detailedStatus en la doc de
     * // micuentaweb.pe. Confirmados con datos reales: CAPTURED, AUTHORISED,
     * // REFUSED. El resto proviene de la nomenclatura de la plataforma.
     */
    public static function desdeRespuestaIzipay(?string $orderStatus, ?string $detailedStatus): self
    {
        $detalle = strtoupper(trim((string) $detailedStatus));
        $orden = strtoupper(trim((string) $orderStatus));

        if (in_array($detalle, self::DETALLES_RECHAZO, true)) {
            return self::Rechazado;
        }

        if (in_array($detalle, self::DETALLES_EXPIRACION, true)) {
            return self::Expirado;
        }

        if (in_array($detalle, self::DETALLES_VERIFICACION, true)) {
            return self::EnVerificacion;
        }

        return match ($orden) {
            'PAID' => self::Pagado,
            'UNPAID' => self::Rechazado,
            'RUNNING', 'PARTIALLY_PAID' => self::EnVerificacion,
            'ABANDONED', 'EXPIRED' => self::Expirado,
            default => self::desconocido($orden, $detalle),
        };
    }

    private static function desconocido(string $orden, string $detalle): self
    {
        Log::warning('Izipay: estado desconocido en la respuesta', [
            'order_status' => $orden,
            'detailed_status' => $detalle,
        ]);

        return self::EnVerificacion;
    }
}
