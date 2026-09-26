<?php

declare(strict_types=1);

namespace App\Enums;

enum MetodoPago: string
{
    case Tarjeta = 'tarjeta';
    case Yape = 'yape';
    case Plin = 'plin';
    case Qr = 'qr';
    case Otro = 'otro';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Tarjeta => 'Tarjeta',
            self::Yape => 'Yape',
            self::Plin => 'Plin',
            self::Qr => 'QR',
            self::Otro => 'Otro',
        };
    }

    /**
     * Deriva el medio de pago desde la respuesta de Izipay (kr-answer).
     *
     * Confirmado con datos reales: transactions.0.transactionDetails.cardDetails
     * (effectiveBrand/pan) solo viene presente en pagos con tarjeta - es el
     * mismo campo que PagoService/PedidoPagoService ya usan para guardar
     * card_brand/card_masked_pan.
     *
     * // TODO: verificar en la doc de micuentaweb.pe el campo/valor exacto
     * // que Izipay devuelve para Yape/Plin/QR en Krypton. paymentMethodType
     * // es el campo estandar de la Transaction de la API V4 de Lyra, pero
     * // no esta confirmado con un pago real de Yape/Plin todavia (pendiente
     * // de que Izipay active esos medios en la cuenta - ver IZIPAY_TESTING.md).
     * // Hasta confirmarlo, cualquier transaccion sin cardDetails que no
     * // calce con un valor reconocido cae en OTRO en vez de adivinar.
     */
    public static function desdeRespuestaIzipay(array $answer): self
    {
        if (data_get($answer, 'transactions.0.transactionDetails.cardDetails.pan') !== null) {
            return self::Tarjeta;
        }

        $tipo = strtoupper((string) data_get($answer, 'transactions.0.paymentMethodType', ''));

        return match (true) {
            str_contains($tipo, 'YAPE') => self::Yape,
            str_contains($tipo, 'PLIN') => self::Plin,
            str_contains($tipo, 'QR') => self::Qr,
            default => self::Otro,
        };
    }
}
