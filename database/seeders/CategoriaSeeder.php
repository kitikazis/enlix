<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        Categoria::firstOrCreate(
            ['slug' => 'pruebas'],
            ['nombre' => 'Pruebas']
        );
    }
}
