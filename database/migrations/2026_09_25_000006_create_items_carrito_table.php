<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items_carrito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrito_id')->constrained('carritos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->unsignedInteger('cantidad');
            // Precio al momento de agregar al carrito. El checkout SIEMPRE
            // recalcula contra productos.precio_centimos antes de cobrar
            // (ver plan de Fase 4) - este snapshot es solo para mostrar el
            // carrito sin recalcular en cada render.
            $table->unsignedInteger('precio_unitario_centimos');
            $table->timestamps();

            // Un mismo producto no puede estar dos veces en el mismo carrito:
            // agregarlo de nuevo debe sumar cantidad, no crear otra fila.
            $table->unique(['carrito_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_carrito');
    }
};
