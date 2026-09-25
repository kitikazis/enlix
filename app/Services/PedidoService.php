<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoPago;
use App\Models\Carrito;
use App\Models\ItemPedido;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Convierte un carrito en un pedido: recalcula precios contra el catálogo
 * actual (nunca confía en el snapshot del carrito, que pudo quedarse viejo)
 * y reserva stock producto por producto. Todo en una sola transacción: si
 * un producto no tiene stock suficiente, no se reserva nada.
 *
 * NO vacía el carrito aquí a propósito: eso lo hace el llamador
 * (CheckoutController) solo después de que Izipay confirme que pudo crear
 * el formToken. Si el carrito se vaciara aquí y luego Izipay fallara, el
 * cliente se quedaría sin carrito Y sin pago.
 */
class PedidoService
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * @param  array{nombre_cliente:string,email:string,telefono:string,tipo_documento:string,numero_documento:string,tipo_comprobante:string,razon_social:?string,metodo_entrega:string,direccion:?string,distrito:?string,ciudad:?string,referencia:?string}  $datosCliente
     *
     * @throws RuntimeException si el carrito está vacío o algún producto no tiene stock suficiente
     */
    public function crearDesdeCarrito(Carrito $carrito, array $datosCliente): Pedido
    {
        return DB::transaction(function () use ($carrito, $datosCliente) {
            $items = $carrito->items()->with('producto')->get();

            if ($items->isEmpty()) {
                throw new RuntimeException('Tu carrito está vacío.');
            }

            $codigo = $this->generarCodigo();

            $pedido = Pedido::create($datosCliente + [
                'codigo' => $codigo,
                'subtotal_centimos' => 0,
                'costo_envio_centimos' => 0,
                'descuento_centimos' => 0,
                'total_centimos' => 0,
                'estado_pago' => EstadoPago::Pendiente,
            ]);

            $subtotal = 0;

            foreach ($items as $item) {
                $producto = $item->producto;

                if ($producto === null || ! $producto->activo) {
                    throw new RuntimeException('Uno de los productos de tu carrito ya no está disponible. Revisa tu carrito.');
                }

                // Precio SIEMPRE del catálogo actual, no del snapshot del carrito
                // (que pudo quedarse viejo si el admin cambió el precio).
                $precioActual = $producto->precio_centimos;
                $subtotalItem = $precioActual * $item->cantidad;

                $this->stock->reservar($producto, $item->cantidad, $codigo);

                ItemPedido::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $producto->id,
                    'sku' => $producto->sku,
                    'nombre' => $producto->nombre,
                    'precio_unitario_centimos' => $precioActual,
                    'cantidad' => $item->cantidad,
                    'subtotal_centimos' => $subtotalItem,
                ]);

                $subtotal += $subtotalItem;
            }

            // Costo de envío: sin lista de tarifas real todavía (ver nota en
            // CheckoutController), queda en 0 aunque el cliente elija envío a
            // domicilio - no se inventa una tarifa.
            $pedido->update([
                'subtotal_centimos' => $subtotal,
                'total_centimos' => $subtotal,
            ]);

            return $pedido->fresh('items');
        });
    }

    /**
     * Se usa cuando Izipay rechaza crear el formToken justo después de
     * reservar stock: no se deja el pedido en 'pendiente' con stock
     * reservado indefinidamente, se libera ahora mismo.
     */
    public function cancelar(Pedido $pedido): void
    {
        $this->moverAEstadoFinalYLiberarStock($pedido, EstadoPago::Rechazado);
    }

    /**
     * Se usa desde pedidos:liberar-reservas-expiradas cuando el pedido lleva
     * mucho tiempo en 'pendiente' y, al preguntarle a Izipay, la orden
     * resulta que ni siquiera existe (el cliente nunca intentó pagar).
     *
     * @return bool true si de verdad se expiró algo
     */
    public function expirar(Pedido $pedido): bool
    {
        return $this->moverAEstadoFinalYLiberarStock($pedido, EstadoPago::Expirado);
    }

    private function moverAEstadoFinalYLiberarStock(Pedido $pedido, EstadoPago $estado): bool
    {
        return (bool) DB::transaction(function () use ($pedido, $estado): bool {
            $fresco = Pedido::where('id', $pedido->id)->lockForUpdate()->first();

            if ($fresco === null || ! $fresco->estado_pago->puedeTransicionarA($estado)) {
                return false;
            }

            $fresco->estado_pago = $estado;
            $fresco->save();

            foreach ($fresco->items as $item) {
                if ($item->producto !== null) {
                    $this->stock->liberar($item->producto, $item->cantidad, $fresco->codigo);
                }
            }

            return true;
        });
    }

    private function generarCodigo(): string
    {
        return 'ENX-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
    }
}
