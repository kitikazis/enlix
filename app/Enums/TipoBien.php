<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoBien: string
{
    case Producto = 'producto';
    case Servicio = 'servicio';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Producto => 'Producto',
            self::Servicio => 'Servicio',
        };
    }
}
