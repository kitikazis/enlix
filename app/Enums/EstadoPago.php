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
    case Anulado = 'anulado';
    case Expirado = 'expirado';

    /**
     * detailedStatus que el BANCO rechazó: la tarjeta nunca llegó a
     * autorizarse. Distinto de Anulado (sí se autorizó, se canceló después).
     */
    private const DETALLES_RECHAZO = ['REFUSED', 'CAPTURE_FAILED'];

    /**
     * detailedStatus de una autorización que SÍ pasó por el banco, pero se
     * canceló/anuló antes de capturarse (por el comercio o automáticamente).
     * Caso real: una autorización quedó "pagado" y horas después se anuló
     * en el Back Office sin capturarse - ver desdeRespuestaIzipay().
     */
    private const DETALLES_ANULACION = ['CANCELLED'];

    /** detailedStatus que significan "caducó sin completarse". */
    private const DETALLES_EXPIRACION = ['EXPIRED', 'ABANDONED'];

    /**
     * detailedStatus que aún no son definitivos: hay que volver a consultar.
     *
     * AUTHORISED (sin "TO_VALIDATE") va aquí a propósito: significa que el
     * banco autorizó el cargo pero Izipay TODAVÍA no lo capturó ("En espera
     * de captura" en su Back Office). Mientras no esté capturado, el
     * comercio puede anularlo sin que pase por el banco - confirmado en
     * producción el 24/09: una autorización quedó "Pagado" en nuestro
     * sistema y horas después se anuló en Izipay sin que nada nos avisara,
     * porque 'pagado' es un estado final e inmutable por diseño. Solo
     * CAPTURED es dinero realmente cobrado.
     */
    private const DETALLES_VERIFICACION = [
        'AUTHORISED',
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
            self::Pagado, self::Rechazado, self::Anulado, self::Expirado => true,
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
            self::Anulado => 'Anulado',
            self::Expirado => 'Expirado',
        };
    }

    /** Clases Tailwind de fondo+texto para el badge del dashboard admin. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pendiente => 'bg-pendiente-bg text-pendiente-text',
            self::EnVerificacion => 'bg-verificacion-bg text-verificacion-text',
            self::Pagado => 'bg-pagado-bg text-pagado-text',
            self::Rechazado => 'bg-rechazado-bg text-rechazado-text',
            self::Anulado => 'bg-anulado-bg text-anulado-text',
            self::Expirado => 'bg-expirado-bg text-expirado-text',
        };
    }

    /** Clase Tailwind del punto de color que acompaña al badge. */
    public function dotClass(): string
    {
        return match ($this) {
            self::Pendiente => 'bg-pendiente-dot',
            self::EnVerificacion => 'bg-verificacion-dot',
            self::Pagado => 'bg-pagado-dot',
            self::Rechazado => 'bg-rechazado-dot',
            self::Anulado => 'bg-anulado-dot',
            self::Expirado => 'bg-expirado-dot',
        };
    }

    /**
     * detailedStatus reales que caen en este estado (mismos arrays que usa
     * desdeRespuestaIzipay, para que la UI nunca muestre un código distinto
     * al que de verdad se evalúa). Pendiente no tiene: es el valor inicial
     * antes de que Izipay conteste algo.
     */
    public function codigosIzipay(): array
    {
        return match ($this) {
            self::Pendiente => [],
            self::EnVerificacion => self::DETALLES_VERIFICACION,
            self::Pagado => ['CAPTURED'],
            self::Rechazado => self::DETALLES_RECHAZO,
            self::Anulado => self::DETALLES_ANULACION,
            self::Expirado => self::DETALLES_EXPIRACION,
        };
    }

    /**
     * Traduce la respuesta de Izipay (orderStatus + detailedStatus de la
     * transacción) al estado interno.
     *
     * Regla deliberadamente conservadora: SOLO detailedStatus=CAPTURED
     * cuenta como Pagado (dinero realmente cobrado, no solo autorizado). Un
     * cargo "autorizado" pero sin capturar puede anularse sin pasar por el
     * banco - confirmado en producción el 24/09: una autorización quedó
     * "pagado" en nuestro sistema y horas después se anuló en Izipay sin
     * avisarnos, porque 'pagado' es un estado final e inmutable por diseño.
     * Ante cualquier duda (detalle desconocido, ausente, o orderStatus=PAID
     * sin CAPTURED explícito) queda en verificación, nunca se asume pagado.
     *
     * // TODO: verificar la lista completa de detailedStatus en la doc de
     * // micuentaweb.pe. Confirmados con datos reales: CAPTURED, AUTHORISED,
     * // REFUSED. El resto proviene de la nomenclatura de la plataforma.
     */
    public static function desdeRespuestaIzipay(?string $orderStatus, ?string $detailedStatus): self
    {
        $detalle = strtoupper(trim((string) $detailedStatus));
        $orden = strtoupper(trim((string) $orderStatus));

        if ($detalle === 'CAPTURED') {
            return self::Pagado;
        }

        if (in_array($detalle, self::DETALLES_RECHAZO, true)) {
            return self::Rechazado;
        }

        if (in_array($detalle, self::DETALLES_ANULACION, true)) {
            return self::Anulado;
        }

        if (in_array($detalle, self::DETALLES_EXPIRACION, true)) {
            return self::Expirado;
        }

        if (in_array($detalle, self::DETALLES_VERIFICACION, true)) {
            return self::EnVerificacion;
        }

        return match ($orden) {
            // PAID sin CAPTURED explicito: se prefiere verificar de nuevo
            // (el job de conciliacion volvera a preguntar) antes que asumir
            // pagado con datos incompletos.
            'PAID' => self::EnVerificacion,
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
