<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoPago;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Heurística de "esto parece un pago de prueba", solo para mostrarlo en
     * el dashboard (chip "PRUEBA" y aviso de datos de prueba). No hay
     * columna is_test todavía: no hay nada que excluir de verdad, solo se
     * señala. @example.com es el dominio que usan las fábricas de tests;
     * S/1.00 es el monto que se usó para las pruebas reales en producción.
     */
    public function scopeEsPrueba(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('email', 'like', '%@example.com')
                ->orWhere('monto', '<=', 100);
        });
    }

    public function esPrueba(): bool
    {
        return str_ends_with($this->email, '@example.com') || $this->monto <= 100;
    }

    /** Ej: "lu•••@gmail.com", para no mostrar el correo completo en pantalla. */
    public function emailEnmascarado(): string
    {
        [$usuario, $dominio] = array_pad(explode('@', $this->email, 2), 2, '');

        return mb_substr($usuario, 0, 2).'•••@'.$dominio;
    }
}
