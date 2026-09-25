<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo de productos que se venden en /productos.
 *
 * El precio (precio_centimos) es la única fuente de verdad del monto a
 * cobrar: IzipayController::formToken() lo lee de aquí, nunca del
 * navegador, así nadie puede alterar el monto desde el cliente.
 */
class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'marca_id',
        'slug',
        'sku',
        'nombre',
        'descripcion_corta',
        'descripcion',
        'especificaciones',
        'precio_centimos',
        'precio_comparacion_centimos',
        'stock',
        'stock_reservado',
        'umbral_stock_bajo',
        'meses_garantia',
        'peso_gramos',
        'features',
        'orden',
        'activo',
        'destacado',
    ];

    protected $casts = [
        'precio_centimos' => 'integer',
        'precio_comparacion_centimos' => 'integer',
        'stock' => 'integer',
        'stock_reservado' => 'integer',
        'umbral_stock_bajo' => 'integer',
        'meses_garantia' => 'integer',
        'peso_gramos' => 'integer',
        'especificaciones' => 'array',
        'features' => 'array',
        'orden' => 'integer',
        'activo' => 'boolean',
        'destacado' => 'boolean',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(ImagenProducto::class)->orderBy('orden');
    }

    public function movimientosStock(): HasMany
    {
        return $this->hasMany(MovimientoStock::class);
    }

    /** Unidades vendibles ahora mismo (lo que no está apartado por un pedido pendiente). */
    public function stockDisponible(): int
    {
        return $this->stock - $this->stock_reservado;
    }
}
