<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El detailedStatus que manda Izipay (AUTHORISED, CAPTURED, CANCELLED...)
 * hasta ahora solo quedaba dentro del JSON `respuesta`. Se guarda también en
 * columna propia para poder filtrarlo/verlo en el admin sin parsear JSON,
 * clave para diagnosticar casos como "autorizado pero nunca capturado".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->string('detailed_status')->nullable()->after('metodo_pago');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('detailed_status')->nullable()->after('metodo_pago');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('detailed_status');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('detailed_status');
        });
    }
};
