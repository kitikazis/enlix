<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EstadoPago;
use App\Models\Pedido;
use App\Services\IzipayService;
use App\Services\PedidoPagoService;
use App\Services\PedidoService;
use Illuminate\Console\Command;

/**
 * Libera la reserva de stock de los pedidos que llevan más de 20 minutos en
 * 'pendiente' sin que Izipay reconozca ningún intento de pago real.
 *
 * Mismo criterio de seguridad que izipay:expirar-pendientes: nunca expira a
 * ciegas, siempre pregunta primero a Izipay el estado real de la orden (un
 * pago pudo completarse aunque el navegador nunca volviera a /checkout).
 * Solo se libera cuando Izipay confirma que esa orden no existe.
 */
class PedidosLiberarReservasExpiradas extends Command
{
    protected $signature = 'pedidos:liberar-reservas-expiradas';

    protected $description = "Expira los pedidos 'pendiente' de más de 20 minutos y libera el stock reservado, consultando antes su estado real en Izipay";

    public function handle(IzipayService $izipay, PedidoPagoService $pedidosPago, PedidoService $pedidos): int
    {
        $vencidos = Pedido::where('estado_pago', EstadoPago::Pendiente)
            ->whereNotNull('izipay_order_id')
            ->where('created_at', '<', now()->subMinutes(20))
            ->get();

        $expirados = 0;
        $recuperados = 0;

        foreach ($vencidos as $pedido) {
            $consulta = $izipay->consultarOrden((string) $pedido->izipay_order_id);

            if (! $consulta['ok']) {
                // Izipay no respondió: se reintenta en la siguiente corrida.
                continue;
            }

            if ($consulta['encontrada']) {
                $resultado = $pedidosPago->registrar($consulta['answer'], PedidoPagoService::ORIGEN_CONCILIACION);

                if (($resultado['estado'] ?? null) !== EstadoPago::Pendiente) {
                    $recuperados++;
                }

                continue;
            }

            if ($pedidos->expirar($pedido)) {
                $expirados++;
            }
        }

        $this->info("Revisados: {$vencidos->count()} | expirados: {$expirados} | resueltos con Izipay: {$recuperados}");

        return self::SUCCESS;
    }
}
