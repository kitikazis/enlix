<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * pedidos:liberar-reservas-expiradas filtra por estado_pago + created_at
 * cada 5 minutos (ver routes/console.php); sin índice, esa consulta hace
 * table scan completo a medida que crece la tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->index('estado_pago');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropIndex(['estado_pago']);
        });
    }
};
