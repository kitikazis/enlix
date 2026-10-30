<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Fotos adicionales de un producto; la principal sigue en productos.imagen. */
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

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::disk('public')->url($this->ruta),
        );
    }

    protected function thumbUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::disk('public')->url(Str::replaceFirst('productos/', 'productos/thumbs/', $this->ruta)),
        );
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
