<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de un pedido. Guarda snapshot propio (sku, nombre, precio) a
 * propósito: no debe cambiar si el producto se edita o desactiva después.
 */
class ItemPedido extends Model
{
    protected $table = 'items_pedido';

    protected $fillable = [
        'pedido_id',
        'producto_id',
        'sku',
        'nombre',
        'precio_unitario_centimos',
        'cantidad',
        'subtotal_centimos',
    ];

    protected $casts = [
        'precio_unitario_centimos' => 'integer',
        'cantidad' => 'integer',
        'subtotal_centimos' => 'integer',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
