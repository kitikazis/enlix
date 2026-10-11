<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Carrito;
use App\Models\ItemCarrito;
use App\Models\Producto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Carrito de invitado, identificado por una cookie propia (no la sesión de
 * Laravel: esta dura 30 días a propósito, para sobrevivir aunque la sesión
 * expire). La cookie solo se emite la primera vez que se agrega algo - así
 * no se crea una fila `carritos` vacía por cada visita a /productos.
 */
class CarritoService
{
    private const COOKIE = 'carrito_session';

    private const COOKIE_DIAS = 30;

    /**
     * Memoiza el carrito resuelto en esta instancia. Necesario porque
     * Cookie::get() lee la cookie de la petición ENTRANTE - dentro de la
     * misma petición que recién creó el carrito y encoló la cookie con
     * Cookie::queue() (que solo se envía en la respuesta), Cookie::get()
     * todavía no la vería y actual() parecería no encontrar nada.
     */
    private ?Carrito $carrito = null;

    private bool $resuelto = false;

    /** El carrito de este visitante si ya tiene uno (cookie + fila en BD), o null. */
    public function actual(): ?Carrito
    {
        if ($this->resuelto) {
            return $this->carrito;
        }

        $this->resuelto = true;
        $sessionId = Cookie::get(self::COOKIE);

        $this->carrito = $sessionId ? Carrito::where('session_id', $sessionId)->first() : null;

        return $this->carrito;
    }

    /**
     * Suma de cantidades para el contador del header. Solo cuenta items
     * "disponibles" (producto activo y con stock) - mismo criterio que
     * resumen()['cantidad_total'], para que el badge nunca muestre un
     * número distinto al título "Carrito (N productos)" de /carrito.
     */
    public function cantidadTotal(): int
    {
        $carrito = $this->actual();

        if ($carrito === null) {
            return 0;
        }

        return (int) $carrito->items()->with('producto')->get()
            ->filter(fn (ItemCarrito $item) => $item->producto !== null
                && ! $item->producto->trashed()
                && $item->producto->activo
                && $item->producto->stockDisponible() > 0)
            ->sum('cantidad');
    }

    /**
     * Resumen serializable del carrito (items + totales), usado tanto por
     * CarritoController (JSON) como por CheckoutController (para mostrar el
     * pedido antes de pagar).
     *
     * Solo cuenta (cantidad_total/subtotal_centimos) los items "disponibles"
     * (producto activo y con stock): los agotados/desactivados se listan en
     * 'items' igual (para la seccion "no disponibles" de /carrito) pero no
     * suman al total ni deben pasar al checkout.
     *
     * @return array{cantidad_total: int, subtotal_centimos: int, descuento_centimos: int, items: Collection}
     */
    public function resumen(): array
    {
        $carrito = $this->actual();
        // withTrashed(): un producto eliminado (soft delete) debe poder
        // seguir mostrando su nombre/imagen en la seccion "no disponibles"
        // en vez de un item huerfano sin info.
        $items = $carrito?->items()
            ->with(['producto' => fn ($q) => $q->withTrashed()->with('marca', 'categoria')])
            ->get() ?? collect();

        $itemsData = $items->map(function (ItemCarrito $item) {
            $producto = $item->producto;
            $disponible = $producto !== null && ! $producto->trashed() && $producto->activo && $producto->stockDisponible() > 0;

            return [
                'id' => $item->id,
                'producto_id' => $item->producto_id,
                'nombre' => $producto?->nombre ?? '(producto ya no disponible)',
                'slug' => $producto?->slug,
                'imagen_url' => $producto?->imagen_thumb_url,
                'marca_o_categoria' => $producto?->marca?->nombre ?? $producto?->categoria?->nombre,
                'categoria_slug' => $producto?->categoria?->slug,
                'cantidad' => $item->cantidad,
                'precio_unitario_centimos' => $item->precio_unitario_centimos,
                'precio_comparacion_centimos' => $producto?->precio_comparacion_centimos,
                'subtotal_centimos' => $item->subtotalCentimos(),
                'stock_disponible' => $producto?->stockDisponible() ?? 0,
                'disponible' => $disponible,
            ];
        })->values();

        $disponibles = $itemsData->where('disponible', true);
        $descuentoCentimos = $disponibles->sum(function (array $item) {
            $precioLista = $item['precio_comparacion_centimos'] ?? $item['precio_unitario_centimos'];

            return max(0, $precioLista - $item['precio_unitario_centimos']) * $item['cantidad'];
        });

        return [
            'cantidad_total' => (int) $disponibles->sum('cantidad'),
            'subtotal_centimos' => (int) $disponibles->sum('subtotal_centimos'),
            'descuento_centimos' => (int) $descuentoCentimos,
            'items' => $itemsData,
        ];
    }

    /**
     * Agrega `$cantidad` unidades de un producto (o las suma si ya estaba en
     * el carrito). Nunca deja el carrito con más unidades que el stock
     * disponible ahora mismo - esto NO reserva stock (eso es al crear el
     * pedido, con lockForUpdate sobre el producto), solo evita ofrecer al
     * carrito algo que ya sabemos que no hay.
     *
     * @throws \RuntimeException si no hay stock suficiente
     */
    public function agregarItem(Producto $producto, int $cantidad): ItemCarrito
    {
        if ($cantidad < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser al menos 1.');
        }

        return DB::transaction(function () use ($producto, $cantidad) {
            // Bloquea la fila del producto mientras se calcula el disponible,
            // para no dejar pasar a dos agregados concurrentes que por
            // separado parecen válidos pero juntos superan el stock.
            $producto = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();
            $carrito = $this->obtenerOCrear();

            $item = ItemCarrito::where('carrito_id', $carrito->id)
                ->where('producto_id', $producto->id)
                ->first();

            $cantidadFinal = ($item?->cantidad ?? 0) + $cantidad;
            $this->verificarStock($producto, $cantidadFinal);

            if ($item) {
                $item->update([
                    'cantidad' => $cantidadFinal,
                    'precio_unitario_centimos' => $producto->precio_centimos,
                ]);

                return $item;
            }

            return ItemCarrito::create([
                'carrito_id' => $carrito->id,
                'producto_id' => $producto->id,
                'cantidad' => $cantidadFinal,
                'precio_unitario_centimos' => $producto->precio_centimos,
            ]);
        });
    }

    /**
     * @throws \RuntimeException si no hay stock suficiente
     */
    public function actualizarCantidad(ItemCarrito $item, int $cantidad): ItemCarrito
    {
        if ($cantidad < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser al menos 1.');
        }

        return DB::transaction(function () use ($item, $cantidad) {
            $producto = Producto::whereKey($item->producto_id)->lockForUpdate()->firstOrFail();
            $this->verificarStock($producto, $cantidad);

            $item->update([
                'cantidad' => $cantidad,
                'precio_unitario_centimos' => $producto->precio_centimos,
            ]);

            return $item;
        });
    }

    public function eliminarItem(ItemCarrito $item): void
    {
        $item->delete();
    }

    /** @throws \RuntimeException */
    private function verificarStock(Producto $producto, int $cantidadDeseada): void
    {
        $disponible = $producto->stockDisponible();

        if ($cantidadDeseada > $disponible) {
            throw new \RuntimeException(
                $disponible > 0
                    ? "Solo quedan {$disponible} unidades disponibles de {$producto->nombre}."
                    : "{$producto->nombre} está agotado."
            );
        }
    }

    private function obtenerOCrear(): Carrito
    {
        $carrito = $this->actual();

        if ($carrito) {
            return $carrito;
        }

        $sessionId = (string) Str::uuid();

        $carrito = Carrito::create([
            'session_id' => $sessionId,
            'expira_en' => now()->addDays(self::COOKIE_DIAS),
        ]);

        Cookie::queue(self::COOKIE, $sessionId, self::COOKIE_DIAS * 24 * 60);

        $this->carrito = $carrito;
        $this->resuelto = true;

        return $carrito;
    }
}
