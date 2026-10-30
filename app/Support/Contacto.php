<?php

namespace App\Support;

/**
 * Datos de contacto comerciales de Enlix y enlaces de WhatsApp con
 * mensaje precargado (así el lead llega indicando qué le interesa).
 */
class Contacto
{
    public const TELEFONO = '963 885 176';

    public const TELEFONO_TEL = '+51963885176';

    public const WHATSAPP = '51963885176';

    public const MENSAJE_GENERAL = 'Hola Enlix, me gustaría obtener más información sobre sus servicios.';

    /** Enlace wa.me con el mensaje dado (o el general). */
    public static function whatsapp(?string $mensaje = null): string
    {
        return 'https://wa.me/'.self::WHATSAPP.'?text='.rawurlencode($mensaje ?? self::MENSAJE_GENERAL);
    }

    /** Enlace wa.me para cotizar un servicio concreto. */
    public static function whatsappServicio(string $servicio): string
    {
        return self::whatsapp("Hola Enlix, quiero cotizar el servicio de {$servicio}.");
    }

    /** Enlace wa.me con el producto ya indicado. */
    public static function whatsappProducto(string $producto): string
    {
        return self::whatsapp("Hola Enlix, quiero más información sobre {$producto}.");
    }
}
