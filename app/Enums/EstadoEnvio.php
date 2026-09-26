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

    /**
     * Reusa los tokens de color de EstadoPago (definidos en resources/css/admin.css)
     * en vez de crear una paleta nueva solo para esto: Preparando/Enviado
     * son "en curso" (igual que en_verificacion), Entregado es "éxito"
     * (igual que pagado), Cancelado es "cerrado sin más" (igual que anulado).
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pendiente => 'bg-pendiente-bg text-pendiente-text',
            self::Preparando, self::Enviado => 'bg-verificacion-bg text-verificacion-text',
            self::Entregado => 'bg-pagado-bg text-pagado-text',
            self::Cancelado => 'bg-anulado-bg text-anulado-text',
        };
    }

    public function dotClass(): string
    {
        return match ($this) {
            self::Pendiente => 'bg-pendiente-dot',
            self::Preparando, self::Enviado => 'bg-verificacion-dot',
            self::Entregado => 'bg-pagado-dot',
            self::Cancelado => 'bg-anulado-dot',
        };
    }
}
