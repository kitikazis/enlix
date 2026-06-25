<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de un pago realizado con Culqi.
 *
 * Se guarda tras un cargo exitoso. Requiere haber corrido la migración
 * create_pagos_table (php artisan migrate). Si aún no hay base de datos,
 * el pago igual se procesa; solo no se persiste (ver CheckoutController).
 */
class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'producto',
        'email',
        'monto',
        'moneda',
        'culqi_charge_id',
        'estado',
        'respuesta',
    ];

    protected $casts = [
        'monto'     => 'integer',
        'respuesta' => 'array',
    ];
}
