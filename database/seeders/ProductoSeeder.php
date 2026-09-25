<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Support\Producto as CatalogoProducto;
use Illuminate\Database\Seeder;

/**
 * Reemplaza los planes de prueba del catálogo viejo por un único producto
 * de prueba para validar el flujo de pago del carrito nuevo.
 *
 * No se borran las filas viejas (`plan-web-*`, "Prueba"): se desactivan,
 * igual que hace Admin\ProductosController::alternarActivo, porque pueden
 * tener pagos históricos (tabla `pagos`) que referencian su slug sin
 * llave foránea y deben poder seguir mostrándose.
 */
class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        Producto::whereIn('slug', [
            'plan-web-basico',
            'plan-web-profesional',
            'plan-web-empresarial',
        ])->orWhere('nombre', 'Prueba')->update(['activo' => false]);

        $categoriaPruebas = Categoria::where('slug', 'pruebas')->first();

        Producto::firstOrCreate(
            ['sku' => 'TEST-001'],
            [
                'categoria_id' => $categoriaPruebas?->id,
                'marca_id' => null,
                'slug' => 'producto-de-prueba',
                'nombre' => 'Producto de prueba',
                'descripcion_corta' => 'Producto para validar el flujo de pago',
                'descripcion' => 'Producto de prueba',
                'especificaciones' => null,
                'precio_centimos' => 100,
                'precio_comparacion_centimos' => null,
                'stock' => 10,
                'activo' => true,
            ]
        );

        CatalogoProducto::limpiarCache();
    }
}
