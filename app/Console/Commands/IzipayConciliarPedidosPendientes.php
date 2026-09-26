<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EstadoPago;
use App\Models\Pedido;
use App\Services\IzipayService;
use App\Services\PedidoPagoService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Equivalente a izipay:conciliar-pendientes, pero para `pedidos` (checkout
 * del carrito). Hasta esta fecha, un pedido que quedaba en 'en_verificacion'
 * (autorizado en el navegador, esperando el IPN) no tenía ningún mecanismo
 * de rescate si esa IPN se perdía: pedidos:liberar-reservas-expiradas solo
 * revisa 'pendiente' (20 min), nunca 'en_verificacion'. Este comando cierra
 * ese hueco preguntándole a Izipay, igual que hace el flujo viejo de
 * `pagos` - ver IzipayConciliarPendientes.
 */
class IzipayConciliarPedidosPendientes extends Command
{
    protected $signature = 'izipay:conciliar-pedidos-pendientes';

    protected $description = "Consulta en Izipay los pedidos en 'en_verificacion' y los actualiza (cubre IPN perdidas)";

    private const ESPERA_MINUTOS = 2;

    private const LIMITE_VERIFICACION_HORAS = 72;

    private const ALERTA_HORAS = 1;

    public function handle(IzipayService $izipay, PedidoPagoService $pedidosPago): int
    {
        $porRevisar = $this->porRevisar();

        if ($porRevisar->isEmpty()) {
            $this->info('No hay pedidos por conciliar.');

            return self::SUCCESS;
        }

        $this->alertarSiHayDemoras($porRevisar);

        $resueltos = 0;
        $sinRespuesta = 0;

        foreach ($porRevisar as $pedido) {
            $consulta = $izipay->consultarOrden((string) $pedido->izipay_order_id);

            if (! $consulta['ok']) {
                $sinRespuesta++;

                continue;
            }

            if (! $consulta['encontrada']) {
                continue;
            }

            $resultado = $pedidosPago->registrar($consulta['answer'], PedidoPagoService::ORIGEN_CONCILIACION);
            $estado = $resultado['estado'] ?? null;

            if ($estado instanceof EstadoPago && $estado->esFinal()) {
                $resueltos++;
            }
        }

        $this->info("Revisados: {$porRevisar->count()} | resueltos: {$resueltos} | sin respuesta de Izipay: {$sinRespuesta}");

        return self::SUCCESS;
    }

    /** @return Collection<int, Pedido> */
    private function porRevisar(): Collection
    {
        return Pedido::query()
            ->where('estado_pago', EstadoPago::EnVerificacion)
            ->whereNotNull('izipay_order_id')
            ->where('created_at', '<', now()->subMinutes(self::ESPERA_MINUTOS))
            ->where('created_at', '>', now()->subHours(self::LIMITE_VERIFICACION_HORAS))
            ->orderBy('created_at')
            ->get();
    }

    /** @param  Collection<int, Pedido>  $porRevisar */
    private function alertarSiHayDemoras(Collection $porRevisar): void
    {
        $demorados = $porRevisar->filter(
            fn (Pedido $pedido) => $pedido->created_at->lt(now()->subHours(self::ALERTA_HORAS))
        );

        if ($demorados->isEmpty()) {
            return;
        }

        Log::warning('Izipay pedidos: hay pedidos sin resolver mas de una hora despues del intento', [
            'total' => $demorados->count(),
            'pedido_id_mas_antiguo' => $demorados->first()->id,
            'izipay_order_id_mas_antiguo' => $demorados->first()->izipay_order_id,
        ]);
    }
}
