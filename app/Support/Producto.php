<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Producto as ProductoModel;
use Illuminate\Support\Facades\Cache;

/**
 * Catálogo de productos de Enlix.
 *
 * Lee de la tabla `productos` (antes vivía en config/productos.php). Se
 * mantiene esta misma interfaz estática (items()/find()) a propósito: todo
 * el flujo de pago (IzipayController, PagoService) depende de ella, y así
 * el cambio de origen de datos no les afecta.
 *
 * Cacheado 60s: el catálogo cambia poco y esto evita una consulta a BD en
 * cada carga de /productos y en cada formToken.
 */
class Producto
{
    private const CACHE_KEY = 'productos.catalogo';

    private const CACHE_SEGUNDOS = 60;

    /** Productos activos, en orden de exhibición (slug => datos). */
    public static function items(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_SEGUNDOS, function () {
            return ProductoModel::where('activo', true)
                ->orderBy('orden')
                ->orderBy('id')
                ->get()
                ->mapWithKeys(fn (ProductoModel $p) => [$p->slug => self::aArray($p)])
                ->all();
        });
    }

    /**
     * Un producto por su slug (incluye el slug en el array), o null si no
     * existe. A diferencia de items(), SÍ devuelve productos inactivos: un
     * pago que ya estaba en curso cuando se desactivó el producto debe poder
     * seguir validándose (ver PagoService::coincideConLaOrden).
     */
    public static function find(string $slug): ?array
    {
        $producto = ProductoModel::where('slug', $slug)->first();

        return $producto ? self::aArray($producto) : null;
    }

    public static function limpiarCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function aArray(ProductoModel $p): array
    {
        return [
            'slug' => $p->slug,
            'nombre' => $p->nombre,
            'descripcion' => $p->descripcion,
            'precio_centimos' => $p->precio_centimos,
            'features' => $p->features ?? [],
            'activo' => $p->activo,
        ];
    }
}
