<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoPago;
use Illuminate\Database\Eloquent\Model;

/**
 * Registro de un pago realizado con Izipay.
 *
 * La fila se crea en estado 'pendiente' al generar el formToken
 * (IzipayController::formToken) y solo IzipayService/PagoService la
 * actualizan (validar/IPN); nunca crean filas nuevas.
 */
class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'producto',
        'email',
        'monto',
        'moneda',
        'izipay_order_id',
        'transaction_uuid',
        'card_brand',
        'card_masked_pan',
        'estado',
        'respuesta',
    ];

    protected $casts = [
        'monto'     => 'integer',
        'estado'    => EstadoPago::class,
        'respuesta' => 'array',
    ];
}
