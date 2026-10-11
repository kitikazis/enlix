<?php

declare(strict_types=1);

namespace App\Enums;

enum EstadoReclamo: string
{
    case Pendiente = 'pendiente';
    case Atendido = 'atendido';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Atendido => 'Atendido',
        };
    }

    /** Reutiliza los tokens de color que ya existen en admin.css para EstadoPago. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pendiente => 'bg-pendiente-bg text-pendiente-text',
            self::Atendido => 'bg-pagado-bg text-pagado-text',
        };
    }
}
