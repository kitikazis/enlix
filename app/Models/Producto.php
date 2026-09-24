<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'slug',
        'nombre',
        'descripcion',
        'precio_centimos',
        'features',
        'orden',
        'activo',
    ];

    protected $casts = [
        'precio_centimos' => 'integer',
        'features' => 'array',
        'orden' => 'integer',
        'activo' => 'boolean',
    ];
}
