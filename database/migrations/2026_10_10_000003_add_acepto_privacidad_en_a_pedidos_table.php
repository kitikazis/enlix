<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Checkbox de aceptacion de la politica de privacidad en checkout: distinto
 * y ademas del de "terminos y condiciones" que ya existia (columna
 * `terminos` no existe como tal, se exigia pero no se guardaba la fecha -
 * este sí queda registrado porque la norma de proteccion de datos personales
 * lo pide).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->timestamp('acepto_privacidad_en')->nullable()->after('razon_social');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('acepto_privacidad_en');
        });
    }
};
