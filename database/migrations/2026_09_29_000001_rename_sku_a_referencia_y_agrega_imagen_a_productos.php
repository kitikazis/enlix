<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "SKU" pasa a llamarse "Referencia" en el modal de productos y ahora es
 * obligatoria (antes era opcional). Los 3 planes-web originales (de antes
 * de que el campo existiera) no tienen sku, así que se les asigna una
 * referencia única (REF-{id}) antes de poder exigir NOT NULL - ningún
 * producto real de catálogo se queda sin ella.
 *
 * También agrega `imagen`: el modal nuevo maneja una sola imagen principal
 * por producto (con miniatura derivada en storage/app/public/productos/thumbs)
 * en vez de la galería de imagenes_producto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('productos_sku_unique');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->renameColumn('sku', 'referencia');
            $table->string('imagen')->nullable()->after('descripcion_corta');
        });

        DB::table('productos')->whereNull('referencia')->pluck('id')->each(
            fn ($id) => DB::table('productos')->where('id', $id)->update(['referencia' => 'REF-'.$id])
        );

        Schema::table('productos', function (Blueprint $table) {
            $table->string('referencia', 50)->nullable(false)->change();
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->unique('referencia');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('productos_referencia_unique');
            $table->dropColumn('imagen');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->renameColumn('referencia', 'sku');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->string('sku', 50)->nullable()->change();
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->unique('sku');
        });
    }
};
