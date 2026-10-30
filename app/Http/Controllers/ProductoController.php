<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Producto as ProductoModel;
use App\Support\Producto;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Página de detalle de un producto de la tienda (/productos/{slug}).
 *
 * Lee el modelo directo y no el catálogo cacheado de App\Support\Producto:
 * aquí se decide la compra, así que precio y stock deben estar al día. Los
 * productos eliminados ya dan 404 por SoftDeletes; los inactivos, por abajo.
 */
class ProductoController extends Controller
{
    public function show(ProductoModel $producto): View
    {
        abort_unless($producto->activo, 404);

        $producto->load(['categoria', 'marca', 'imagenes']);

        $resumen = trim(html_entity_decode(strip_tags($producto->descripcion), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return view('producto', [
            'titulo' => "{$producto->nombre} - Enlix",
            'metaDescription' => Str::limit($resumen, 155),
            'seoImagen' => $producto->imagen ? $producto->imagen_url : null,
            'seoImagenAlt' => $producto->nombre,
            'current' => 'productos',
            'productoDetalle' => $producto,
            'relacionados' => Producto::relacionados($producto),
        ]);
    }
}
