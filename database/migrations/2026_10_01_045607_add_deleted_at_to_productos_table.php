<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft delete para productos: "eliminar" en el admin oculta el producto
 * del listado y del catálogo público (igual que desactivar) pero conserva
 * la fila - items_pedido.producto_id ya es nullOnDelete pensando en esto,
 * y cada línea de pedido guarda su propio snapshot de nombre/precio, así
 * que el historial nunca depende de que el producto siga vivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
