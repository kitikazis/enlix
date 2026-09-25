<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemCarrito extends Model
{
    protected $table = 'items_carrito';

    protected $fillable = [
        'carrito_id',
        'producto_id',
        'cantidad',
        'precio_unitario_centimos',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario_centimos' => 'integer',
    ];

    public function carrito(): BelongsTo
    {
        return $this->belongsTo(Carrito::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function subtotalCentimos(): int
    {
        return $this->precio_unitario_centimos * $this->cantidad;
    }
}
