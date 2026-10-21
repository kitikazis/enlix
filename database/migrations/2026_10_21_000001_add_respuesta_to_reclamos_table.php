<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de la respuesta del proveedor al marcar un reclamo como
 * atendido: qué se respondió y cuándo, para poder demostrar que se cumplió
 * el plazo legal (fecha_limite_respuesta). Ambas nullable: los reclamos
 * atendidos antes de esta migración no tienen respuesta registrada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reclamos', function (Blueprint $table) {
            $table->text('respuesta')->nullable()->after('estado');
            $table->timestamp('respondido_en')->nullable()->after('respuesta');
        });
    }

    public function down(): void
    {
        Schema::table('reclamos', function (Blueprint $table) {
            $table->dropColumn(['respuesta', 'respondido_en']);
        });
    }
};
