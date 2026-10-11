<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contenido editable de /terminos-y-condiciones y /politica-de-privacidad
 * desde el panel admin (ver Admin\PaginasLegalesController), sin tocar
 * código. `contenido` llega saneado con el mismo HTMLPurifier que ya usa
 * Producto::descripcion(), con un set de tags mas amplio (incluye h2/h3
 * para el indice de secciones de la vista publica).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paginas_legales', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('titulo');
            $table->longText('contenido');
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paginas_legales');
    }
};
