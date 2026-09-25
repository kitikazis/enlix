<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagenProducto extends Model
{
    protected $table = 'imagenes_producto';

    protected $fillable = [
        'producto_id',
        'ruta',
        'texto_alternativo',
        'orden',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
