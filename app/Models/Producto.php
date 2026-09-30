<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

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

    /**
     * Etiquetas permitidas en la descripción (editor enriquecido del admin):
     * negrita, cursiva, listas y enlaces, nada más. Se sanea aquí -en el
     * mutator- y no solo en el controller, para que cualquier vía de
     * escritura (seeders, tinker, futuros imports) quede protegida contra
     * HTML/JS inyectado.
     */
    private const CONFIG_DESCRIPCION_HTML = [
        'HTML.Allowed' => 'p,br,strong,b,em,i,u,ul,ol,li,a[href|title|target|rel]',
        'HTML.TargetBlank' => true,
        'AutoFormat.RemoveEmpty' => true,
    ];

    protected $fillable = [
        'categoria_id',
        'marca_id',
        'slug',
        'referencia',
        'nombre',
        'descripcion_corta',
        'imagen',
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

    protected function descripcion(): Attribute
    {
        return Attribute::make(
            set: fn (?string $valor) => $valor !== null ? Purifier::clean($valor, self::CONFIG_DESCRIPCION_HTML) : null,
        );
    }

    /** URL pública de la imagen principal, o un placeholder si el producto no tiene. */
    protected function imagenUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->imagen
                ? Storage::disk('public')->url($this->imagen)
                : asset('assets/img/producto-placeholder.svg'),
        );
    }

    /** URL de la miniatura de 300px (listado admin), derivada de la ruta de `imagen`. */
    protected function imagenThumbUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->imagen
                ? Storage::disk('public')->url(Str::replaceFirst('productos/', 'productos/thumbs/', $this->imagen))
                : asset('assets/img/producto-placeholder.svg'),
        );
    }

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
