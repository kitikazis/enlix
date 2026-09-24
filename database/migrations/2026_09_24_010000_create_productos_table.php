<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mueve el catalogo de productos de config/productos.php a base de datos,
 * para que el admin pueda gestionarlo desde el panel sin tocar codigo.
 *
 * El precio SIGUE siendo la unica fuente de verdad del monto a cobrar
 * (IzipayController::formToken lo lee de aqui, nunca del navegador) - eso
 * no cambia, solo cambia de donde vive el dato.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('nombre');
            $table->text('descripcion');
            $table->unsignedInteger('precio_centimos');
            $table->json('features')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Migra el catalogo tal cual estaba en config/productos.php, con el
        // precio original (no el S/1 temporal que quedo puesto para las
        // pruebas de produccion de hoy).
        DB::table('productos')->insert([
            [
                'slug' => 'plan-web-basico',
                'nombre' => 'Plan Web Básico',
                'descripcion' => 'Sitio web institucional listo para tu empresa, con diseño responsive y formulario de contacto.',
                'precio_centimos' => 9900,
                'features' => json_encode(['Hasta 5 secciones', 'Diseño responsive', 'Formulario de contacto', 'Soporte por 1 mes']),
                'orden' => 1,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'plan-web-profesional',
                'nombre' => 'Plan Web Profesional',
                'descripcion' => 'Todo lo del plan básico más posicionamiento, blog y panel para gestionar tu contenido.',
                'precio_centimos' => 19900,
                'features' => json_encode(['Hasta 12 secciones', 'Optimización SEO básica', 'Blog y noticias', 'Panel de administración', 'Soporte por 3 meses']),
                'orden' => 2,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'plan-web-empresarial',
                'nombre' => 'Plan Web Empresarial',
                'descripcion' => 'Solución a medida con integraciones, tienda en línea y soporte prioritario para tu negocio.',
                'precio_centimos' => 34900,
                'features' => json_encode(['Secciones ilimitadas', 'Tienda en línea', 'Integraciones a medida', 'Soporte prioritario por 6 meses']),
                'orden' => 3,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
