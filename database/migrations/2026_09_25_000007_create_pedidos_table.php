<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pedidos del carrito nuevo (multi-producto). A propósito NO reemplaza a
 * `pagos` (motor de pago de 1 producto por pago, ya en producción con
 * cargos reales) - conviven: `pagos` sigue como está, `pedidos` es el
 * camino nuevo para el checkout con carrito. Mismos nombres de columna que
 * `pagos` para los campos de Izipay (izipay_order_id, transaction_uuid,
 * card_brand, card_masked_pan) para no introducir una convención distinta.
 *
 * estado_pago usa los mismos valores que App\Enums\EstadoPago (columna
 * pagos.estado). estado_envio es un concepto aparte (logística, no cobro) y
 * queda null hasta que el pedido esté pagado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('nombre_cliente', 100);
            $table->string('email');
            $table->string('telefono', 20);
            $table->string('tipo_documento', 3);
            $table->string('numero_documento', 15);
            $table->string('tipo_comprobante', 10);
            $table->string('razon_social', 150)->nullable();

            $table->string('metodo_entrega', 10);
            $table->string('direccion')->nullable();
            $table->string('distrito', 100)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('referencia')->nullable();

            $table->unsignedInteger('subtotal_centimos');
            $table->unsignedInteger('costo_envio_centimos')->default(0);
            $table->unsignedInteger('descuento_centimos')->default(0);
            $table->unsignedInteger('total_centimos');

            $table->string('estado_pago')->default('pendiente');
            $table->string('estado_envio')->nullable();

            $table->string('izipay_order_id')->nullable()->unique();
            $table->string('transaction_uuid')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('card_masked_pan')->nullable();

            $table->timestamp('pagado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
