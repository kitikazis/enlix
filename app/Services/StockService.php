<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MovimientoStock;
use App\Models\Producto;
use RuntimeException;

/**
 * Toda alta/baja de stock pasa por aquí y queda registrada en
 * `movimientos_stock`. Cada método bloquea su propia fila de `productos`
 * (lockForUpdate) antes de tocarla - los llamadores (PedidoService,
 * PedidoPagoService) ya corren dentro de una transacción propia, así que
 * este lock queda contenido en ella.
 */
class StockService
{
    /**
     * @throws RuntimeException si no hay stock disponible suficiente
     */
    public function reservar(Producto $producto, int $cantidad, string $referencia): void
    {
        $fresco = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();

        if ($cantidad > $fresco->stockDisponible()) {
            throw new RuntimeException(
                $fresco->stockDisponible() > 0
                    ? "Solo quedan {$fresco->stockDisponible()} unidades disponibles de {$fresco->nombre}."
                    : "{$fresco->nombre} está agotado."
            );
        }

        $fresco->increment('stock_reservado', $cantidad);

        $this->registrarMovimiento($fresco, 'reserva', $cantidad, $referencia);
    }

    /** Reserva -> venta confirmada: sale del stock físico y de lo reservado. */
    public function confirmar(Producto $producto, int $cantidad, string $referencia): void
    {
        $fresco = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();

        $fresco->decrement('stock', $cantidad);
        $fresco->decrement('stock_reservado', $cantidad);

        $this->registrarMovimiento($fresco, 'salida', $cantidad, $referencia);
    }

    /** Pago rechazado/anulado/expirado: la reserva se libera, el stock físico no se toca. */
    public function liberar(Producto $producto, int $cantidad, string $referencia): void
    {
        $fresco = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();

        $fresco->decrement('stock_reservado', $cantidad);

        $this->registrarMovimiento($fresco, 'liberacion', $cantidad, $referencia);
    }

    private function registrarMovimiento(Producto $producto, string $tipo, int $cantidad, string $referencia): void
    {
        MovimientoStock::create([
            'producto_id' => $producto->id,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'referencia' => $referencia,
        ]);
    }
}
