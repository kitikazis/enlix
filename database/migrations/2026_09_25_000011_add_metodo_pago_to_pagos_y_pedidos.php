<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->string('metodo_pago')->nullable()->after('card_masked_pan');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('metodo_pago')->nullable()->after('card_masked_pan');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('metodo_pago');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('metodo_pago');
        });
    }
};
