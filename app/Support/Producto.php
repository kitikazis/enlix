<?php

namespace App\Support;

/**
 * Catálogo de productos de Enlix.
 *
 * Lee los datos desde config/productos.php (mismo patrón que App\Support\Catalogo).
 * Si en el futuro los productos pasan a base de datos, solo se cambia esta clase.
 */
class Producto
{
    /** Todos los productos (slug => datos). */
    public static function items(): array
    {
        return config('productos.items', []);
    }

    /** Un producto por su slug (incluye el slug en el array), o null si no existe. */
    public static function find(string $slug): ?array
    {
        $producto = config("productos.items.$slug");

        if ($producto === null) {
            return null;
        }

        return array_merge($producto, ['slug' => $slug]);
    }
}
