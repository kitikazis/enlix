<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriaSeeder extends Seeder
{
    /** Categorías de componentes de PC. Solo nombres (organización del catálogo), sin productos asociados aún. */
    private const CATEGORIAS = [
        'Tarjetas gráficas',
        'Procesadores',
        'Memoria RAM',
        'Almacenamiento SSD',
        'Placas madre',
        'Fuentes de poder',
        'Gabinetes',
        'Periféricos',
    ];

    public function run(): void
    {
        Categoria::firstOrCreate(
            ['slug' => 'pruebas'],
            ['nombre' => 'Pruebas']
        );

        foreach (self::CATEGORIAS as $nombre) {
            Categoria::firstOrCreate(
                ['slug' => Str::slug($nombre)],
                ['nombre' => $nombre]
            );
        }
    }
}
