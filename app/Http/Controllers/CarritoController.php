<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ItemCarrito;
use App\Models\Producto;
use App\Services\CarritoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarritoController extends Controller
{
    public function __construct(private readonly CarritoService $carritos) {}

    public function index(): View
    {
        return view('carrito', [
            'titulo' => 'Carrito - Enlix',
            'current' => 'productos',
            'resumen' => $this->resumenArray(),
        ]);
    }

    public function mostrar(): JsonResponse
    {
        return response()->json($this->resumenArray());
    }

    public function agregar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'cantidad' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $producto = Producto::where('id', $datos['producto_id'])->where('activo', true)->first();

        if (! $producto) {
            return response()->json(['ok' => false, 'mensaje' => 'Producto no disponible.'], 404);
        }

        try {
            $this->carritos->agregarItem($producto, (int) ($datos['cantidad'] ?? 1));
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'mensaje' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'mensaje' => 'Agregado al carrito.'] + $this->resumenArray());
    }

    public function actualizar(Request $request, ItemCarrito $item): JsonResponse
    {
        $this->autorizarItem($item);

        $datos = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        try {
            $this->carritos->actualizarCantidad($item, (int) $datos['cantidad']);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'mensaje' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true] + $this->resumenArray());
    }

    public function eliminar(ItemCarrito $item): JsonResponse
    {
        $this->autorizarItem($item);

        $this->carritos->eliminarItem($item);

        return response()->json(['ok' => true] + $this->resumenArray());
    }

    /** Evita que alguien edite/borre items de un carrito ajeno adivinando el ID. */
    private function autorizarItem(ItemCarrito $item): void
    {
        $carrito = $this->carritos->actual();

        abort_unless($carrito && $item->carrito_id === $carrito->id, 404);
    }

    private function resumenArray(): array
    {
        $carrito = $this->carritos->actual();
        $items = $carrito?->items()->with('producto')->get() ?? collect();

        $itemsData = $items->map(fn (ItemCarrito $item) => [
            'id' => $item->id,
            'producto_id' => $item->producto_id,
            'nombre' => $item->producto?->nombre ?? '(producto ya no disponible)',
            'slug' => $item->producto?->slug,
            'cantidad' => $item->cantidad,
            'precio_unitario_centimos' => $item->precio_unitario_centimos,
            'subtotal_centimos' => $item->subtotalCentimos(),
            'stock_disponible' => $item->producto?->stockDisponible() ?? 0,
        ])->values();

        return [
            'cantidad_total' => (int) $itemsData->sum('cantidad'),
            'subtotal_centimos' => (int) $itemsData->sum('subtotal_centimos'),
            'items' => $itemsData,
        ];
    }
}
