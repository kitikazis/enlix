<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoReclamo: string
{
    case Reclamo = 'reclamo';
    case Queja = 'queja';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Reclamo => 'Reclamo',
            self::Queja => 'Queja',
        };
    }
}
