<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoReclamo;
use App\Enums\TipoBien;
use App\Enums\TipoReclamo;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Libro de Reclamaciones. El numero correlativo que exige la norma es el
 * `id` autoincremental (ver numeroCorrelativo()); no se guarda aparte.
 */
class Reclamo extends Model
{
    protected $table = 'reclamos';

    /** Dias habiles (lunes a viernes) que la norma da para responder. No contempla feriados. */
    private const PLAZO_DIAS_HABILES = 15;

    protected $fillable = [
        'nombre_consumidor',
        'tipo_documento_consumidor',
        'numero_documento_consumidor',
        'domicilio_consumidor',
        'telefono_consumidor',
        'correo_consumidor',
        'es_menor_edad',
        'nombre_padre_tutor',
        'documento_padre_tutor',
        'tipo_bien',
        'descripcion_bien',
        'monto_centimos',
        'tipo',
        'detalle',
        'pedido_consumidor',
        'estado',
        'respuesta',
        'respondido_en',
        'acepto_privacidad_en',
        'fecha_limite_respuesta',
        'ip_origen',
    ];

    protected $casts = [
        'es_menor_edad' => 'boolean',
        'monto_centimos' => 'integer',
        'tipo_bien' => TipoBien::class,
        'tipo' => TipoReclamo::class,
        'estado' => EstadoReclamo::class,
        'acepto_privacidad_en' => 'datetime',
        'fecha_limite_respuesta' => 'date',
        'respondido_en' => 'datetime',
    ];

    protected function numeroCorrelativo(): Attribute
    {
        return Attribute::make(
            get: fn () => str_pad((string) $this->id, 6, '0', STR_PAD_LEFT),
        );
    }

    /** +15 dias habiles (lunes a viernes) desde ahora, en zona horaria de Lima. */
    public static function calcularFechaLimite(): CarbonImmutable
    {
        $fecha = CarbonImmutable::now('America/Lima');
        $restantes = self::PLAZO_DIAS_HABILES;

        while ($restantes > 0) {
            $fecha = $fecha->addDay();
            if (! $fecha->isWeekend()) {
                $restantes--;
            }
        }

        return $fecha;
    }
}
