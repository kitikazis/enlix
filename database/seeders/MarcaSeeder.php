<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Marca;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarcaSeeder extends Seeder
{
    /** Marcas reales del rubro de componentes de PC. Solo nombres, sin productos asociados aún. */
    private const MARCAS = [
        'NVIDIA',
        'AMD',
        'Intel',
        'Kingston',
        'Corsair',
        'Samsung',
        'Western Digital',
        'ASUS',
        'MSI',
        'Gigabyte',
        'Cooler Master',
        'NZXT',
        'Logitech',
        'Redragon',
    ];

    public function run(): void
    {
        foreach (self::MARCAS as $nombre) {
            Marca::firstOrCreate(
                ['slug' => Str::slug($nombre)],
                ['nombre' => $nombre]
            );
        }
    }
}
