<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Defensa en profundidad para la idempotencia del IPN: hoy la garantiza
 * PagoService (lockForUpdate + estado final inmutable), pero un índice
 * UNIQUE impide a nivel de BD que dos filas compartan la misma transacción.
 *
 * El índice es nullable: los pagos 'pendiente' todavía no tienen uuid y
 * MySQL/SQLite permiten múltiples NULL en una columna UNIQUE.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicados = DB::table('pagos')
            ->select('transaction_uuid')
            ->whereNotNull('transaction_uuid')
            ->groupBy('transaction_uuid')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('transaction_uuid');

        if ($duplicados->isNotEmpty()) {
            throw new RuntimeException(
                'No se puede crear el índice UNIQUE: hay transaction_uuid duplicados ('
                .$duplicados->implode(', ').'). Revísalos antes de migrar.'
            );
        }

        Schema::table('pagos', function (Blueprint $table) {
            $table->unique('transaction_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropUnique(['transaction_uuid']);
        });
    }
};
