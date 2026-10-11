<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Libro de Reclamaciones (obligatorio por ley para todo proveedor en Peru,
 * ver D.S. 101-2022-PCM). El numero correlativo que exige la norma es
 * simplemente el `id` autoincremental: ya es secuencial y unico, no hace
 * falta una columna aparte.
 *
 * fecha_limite_respuesta se calcula al guardar (created_at + 15 dias
 * habiles, ver Reclamo::calcularFechaLimite()) para que el admin la vea sin
 * tener que recalcularla cada vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclamos', function (Blueprint $table) {
            $table->id();

            // Consumidor
            $table->string('nombre_consumidor', 150);
            $table->string('tipo_documento_consumidor', 3);
            $table->string('numero_documento_consumidor', 15);
            $table->string('domicilio_consumidor', 255);
            $table->string('telefono_consumidor', 20);
            $table->string('correo_consumidor');

            // Padre/tutor, solo si el consumidor es menor de edad
            $table->boolean('es_menor_edad')->default(false);
            $table->string('nombre_padre_tutor', 150)->nullable();
            $table->string('documento_padre_tutor', 15)->nullable();

            // Identificacion del bien reclamado
            $table->string('tipo_bien', 10); // producto | servicio
            $table->string('descripcion_bien', 255);
            $table->unsignedInteger('monto_centimos')->nullable();

            $table->string('tipo', 10); // reclamo | queja
            $table->text('detalle');
            $table->text('pedido_consumidor');

            $table->string('estado', 15)->default('pendiente'); // pendiente | atendido
            $table->timestamp('acepto_privacidad_en');
            $table->date('fecha_limite_respuesta');

            $table->string('ip_origen', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamos');
    }
};
