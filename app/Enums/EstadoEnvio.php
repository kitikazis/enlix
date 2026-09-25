<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Estado logístico de un pedido ya pagado. Concepto aparte de EstadoPago
 * (que es solo el cobro): un pedido puede estar "pagado" y seguir
 * "pendiente" de preparar/enviar.
 */
enum EstadoEnvio: string
{
    case Pendiente = 'pendiente';
    case Preparando = 'preparando';
    case Enviado = 'enviado';
    case Entregado = 'entregado';
    case Cancelado = 'cancelado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Preparando => 'Preparando',
            self::Enviado => 'Enviado',
            self::Entregado => 'Entregado',
            self::Cancelado => 'Cancelado',
        };
    }
}
