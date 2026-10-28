<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Oferta del día" de /productos: el admin marca UN producto como oferta y
 * se publica por 24 horas reales (oferta_inicia_en → oferta_termina_en).
 * El contador público cuenta hacia oferta_termina_en, igual para todos los
 * visitantes - no se reinicia por visita.
 *
 * Los textos (titular, bajada, beneficios) se conservan aunque la oferta
 * venza o se desmarque, para poder volver a publicarla sin reescribirlos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('oferta_titular', 120)->nullable()->after('destacado');
            $table->string('oferta_bajada', 300)->nullable()->after('oferta_titular');
            // [{titulo, texto}, ...] - máx. 3, las tarjetas numeradas 01/02/03.
            $table->json('oferta_beneficios')->nullable()->after('oferta_bajada');
            $table->timestamp('oferta_inicia_en')->nullable()->after('oferta_beneficios');
            $table->timestamp('oferta_termina_en')->nullable()->after('oferta_inicia_en');
            $table->index('oferta_termina_en');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['oferta_termina_en']);
            $table->dropColumn(['oferta_titular', 'oferta_bajada', 'oferta_beneficios', 'oferta_inicia_en', 'oferta_termina_en']);
        });
    }
};
