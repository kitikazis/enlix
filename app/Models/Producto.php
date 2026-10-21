<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

/**
 * Catálogo de productos que se venden en /productos.
 *
 * El precio (precio_centimos) es la única fuente de verdad del monto a
 * cobrar: IzipayController::formToken() lo lee de aquí, nunca del
 * navegador, así nadie puede alterar el monto desde el cliente.
 *
 * SoftDeletes a propósito: "eliminar" en el admin no borra la fila (el
 * historial de pedidos/pagos no depende de ella, pero items_pedido.producto_id
 * sigue apuntando aqui con nullOnDelete por si acaso). Eloquent ya excluye
 * los soft-deleted de todas las consultas normales (admin, catálogo
 * público, dashboard) sin tocar nada mas.
 */
class Producto extends Model
{
    use SoftDeletes;

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

    /**
     * Forma canónica de la referencia (sin espacios en los extremos, en
     * mayúsculas). La usan el mutator, el FormRequest y el chequeo en vivo,
     * así "gpu-001 " y "GPU-001" son siempre la misma referencia.
     */
    public static function normalizarReferencia(mixed $referencia): string
    {
        return mb_strtoupper(trim((string) $referencia));
    }

    /**
     * Producto que ya usa esa referencia, INCLUIDOS los de la papelera: es
     * el mismo criterio que el índice único productos_referencia_unique.
     */
    public static function conReferencia(string $referencia, ?int $ignorarId = null): ?self
    {
        return static::withTrashed()
            ->where('referencia', static::normalizarReferencia($referencia))
            ->when($ignorarId, fn ($query, $id) => $query->whereKeyNot($id))
            ->first();
    }

    /**
     * true si la descripción conserva texto visible DESPUÉS del saneado
     * del mutator: el editor vacío manda "<p><br></p>" y un "<script>" lo
     * elimina el Purifier, y en ambos casos se guardaría vacía.
     */
    public static function descripcionTieneTexto(?string $html): bool
    {
        $limpio = Purifier::clean((string) $html, self::CONFIG_DESCRIPCION_HTML);
        $texto = html_entity_decode(strip_tags($limpio), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(str_replace("\u{00A0}", ' ', $texto)) !== '';
    }

    protected function referencia(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $valor) => $valor !== null ? static::normalizarReferencia($valor) : null,
        );
    }

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

    /**
     * Misma heurística que Pago::esPrueba() (S/ 1.00 es el monto que se usó
     * para las pruebas reales en producción), solo para la etiqueta
     * "PRUEBA" del admin: no oculta ni excluye el producto de nada.
     */
    public function esPrueba(): bool
    {
        return $this->precio_centimos <= 100;
    }
}
