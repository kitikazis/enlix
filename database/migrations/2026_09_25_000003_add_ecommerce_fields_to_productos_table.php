<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extiende `productos` (hasta ahora, planes de servicios sin variantes) con
 * los campos que necesita un catálogo de componentes de PC: categoría,
 * marca, SKU, specs técnicas, stock y precio de comparación.
 *
 * No se agrega soft-delete: `productos` ya tiene su propio mecanismo para
 * "ocultar sin perder historial" (columna `activo`, ver el docblock de
 * Admin\ProductosController::alternarActivo) y los pedidos nuevos guardan
 * snapshot propio en `items_pedido`, así que no hace falta otro mecanismo
 * de borrado lógico encima.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('sku', 50)->nullable()->unique()->after('slug');
            $table->string('descripcion_corta', 160)->nullable()->after('sku');
            // socket, chipset, VRAM, TDP, formato, etc. - estructura libre por
            // categoría, no todas las categorías comparten las mismas specs.
            $table->json('especificaciones')->nullable()->after('descripcion');
            $table->unsignedInteger('precio_comparacion_centimos')->nullable()->after('precio_centimos');
            $table->unsignedInteger('stock')->default(0)->after('precio_comparacion_centimos');
            // Apartado por pedidos "pendiente" (ver movimientos_stock). Stock
            // disponible para vender = stock - stock_reservado.
            $table->unsignedInteger('stock_reservado')->default(0)->after('stock');
            $table->unsignedInteger('umbral_stock_bajo')->default(3)->after('stock_reservado');
            $table->unsignedInteger('meses_garantia')->nullable()->after('umbral_stock_bajo');
            $table->unsignedInteger('peso_gramos')->nullable()->after('meses_garantia');
            $table->boolean('destacado')->default(false)->after('activo');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('categoria_id')->nullable()->after('id')->constrained('categorias')->nullOnDelete();
            $table->foreignId('marca_id')->nullable()->after('categoria_id')->constrained('marcas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('categoria_id');
            $table->dropConstrainedForeignId('marca_id');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn([
                'sku',
                'descripcion_corta',
                'especificaciones',
                'precio_comparacion_centimos',
                'stock',
                'stock_reservado',
                'umbral_stock_bajo',
                'meses_garantia',
                'peso_gramos',
                'destacado',
            ]);
        });
    }
};
