<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->string('producto');                       // slug del producto
            $table->string('email');
            $table->unsignedInteger('monto');                 // en céntimos
            $table->string('moneda', 3)->default('PEN');
            $table->string('culqi_charge_id')->nullable()->index();
            $table->string('estado')->default('pendiente');   // pendiente | pagado | rechazado
            $table->json('respuesta')->nullable();            // respuesta cruda de Culqi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
