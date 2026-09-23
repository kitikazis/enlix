<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El rename va en su propio Schema::table(): en SQLite, mezclarlo con
        // agregar columnas/indices en el mismo blueprint genera problemas.
        Schema::table('pagos', function (Blueprint $table) {
            $table->renameColumn('culqi_charge_id', 'izipay_order_id');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->unique('izipay_order_id');
            $table->string('transaction_uuid')->nullable()->after('izipay_order_id');
            $table->string('card_brand')->nullable()->after('transaction_uuid');
            $table->string('card_masked_pan')->nullable()->after('card_brand');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropUnique(['izipay_order_id']);
            $table->dropColumn(['transaction_uuid', 'card_brand', 'card_masked_pan']);
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->renameColumn('izipay_order_id', 'culqi_charge_id');
        });
    }
};
