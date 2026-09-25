<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoPago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pedido del carrito (multi-producto). Coexiste con `Pago` (1 producto por
 * pago, motor ya en producción) - no lo reemplaza, ver docblock de la
 * migración `create_pedidos_table`.
 */
class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = [
        'codigo',
        'user_id',
        'nombre_cliente',
        'email',
        'telefono',
        'tipo_documento',
        'numero_documento',
        'tipo_comprobante',
        'razon_social',
        'metodo_entrega',
        'direccion',
        'distrito',
        'ciudad',
        'referencia',
        'subtotal_centimos',
        'costo_envio_centimos',
        'descuento_centimos',
        'total_centimos',
        'estado_pago',
        'estado_envio',
        'izipay_order_id',
        'transaction_uuid',
        'card_brand',
        'card_masked_pan',
        'respuesta',
        'pagado_en',
    ];

    protected $casts = [
        'subtotal_centimos' => 'integer',
        'costo_envio_centimos' => 'integer',
        'descuento_centimos' => 'integer',
        'total_centimos' => 'integer',
        'estado_pago' => EstadoPago::class,
        'estado_envio' => EstadoEnvio::class,
        'respuesta' => 'array',
        'pagado_en' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemPedido::class);
    }

    public function movimientosStock(): HasMany
    {
        return $this->hasMany(MovimientoStock::class, 'referencia', 'codigo');
    }
}
