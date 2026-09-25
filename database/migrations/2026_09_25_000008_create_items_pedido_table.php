<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items_pedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            // Nullable a propósito: el producto nunca se borra (ver
            // Admin\ProductosController::alternarActivo), pero este snapshot
            // es la fuente de verdad del pedido histórico de todas formas,
            // igual que pagos.producto guarda el slug sin depender de la fila
            // actual del catálogo.
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();

            $table->string('sku', 50)->nullable();
            $table->string('nombre', 150);
            $table->unsignedInteger('precio_unitario_centimos');
            $table->unsignedInteger('cantidad');
            $table->unsignedInteger('subtotal_centimos');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_pedido');
    }
};
