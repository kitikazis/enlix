<?php

declare(strict_types=1);

namespace App\Support;

/** Formato de moneda único del panel admin: siempre "S/", nunca el código ISO (PEN). */
final class Dinero
{
    public static function soles(int $centimos): string
    {
        return 'S/ '.number_format($centimos / 100, 2);
    }
}
