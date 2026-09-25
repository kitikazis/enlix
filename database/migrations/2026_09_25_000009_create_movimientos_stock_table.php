<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de todo movimiento de stock (entrada, salida por venta, reserva
 * al crear un pedido, liberación si se cae el pago, ajuste manual desde el
 * admin). `cantidad` es un entero con signo porque un ajuste manual puede
 * ser negativo (corrección de inventario).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('tipo', 10);
            $table->integer('cantidad');
            $table->string('referencia', 50)->nullable();
            $table->string('nota')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};
