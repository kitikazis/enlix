<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EstadoPago;
use App\Models\Pago;
use App\Models\Pedido;
use App\Services\IzipayService;
use App\Services\PagoService;
use App\Services\PedidoPagoService;
use Illuminate\Console\Command;

/**
 * Herramienta manual de reconciliación, complementaria a los jobs
 * automáticos (izipay:conciliar-pendientes, izipay:conciliar-pedidos-pendientes,
 * izipay:expirar-pendientes, pedidos:liberar-reservas-expiradas), que solo
 * cubren ventanas de tiempo acotadas (hasta 72h). Sirve para:
 *
 * - Forzar la revisión de UNA orden puntual: --order=ENX-...
 * - Barrer todo lo no-final de los últimos N días: --days=7 (por defecto)
 *
 * No crea filas ni cancela nada: solo pregunta a Izipay y aplica el mismo
 * mapeo/transiciones que el IPN (PagoService/PedidoPagoService), así que es
 * seguro correrlo tantas veces como haga falta.
 */
class IzipayReconcile extends Command
{
    protected $signature = 'izipay:reconcile {--days=7 : Revisa pagos/pedidos no finales creados en los últimos N días} {--order= : Revisa una única orden por su izipay_order_id/código}';

    protected $description = 'Reconciliación manual contra Izipay para pagos (pagos) y pedidos (checkout) que no están en estado final';

    public function handle(IzipayService $izipay, PagoService $pagosServicio, PedidoPagoService $pedidosServicio): int
    {
        $orderId = $this->option('order');

        if (is_string($orderId) && $orderId !== '') {
            return $this->revisarUnaOrden($orderId, $izipay, $pagosServicio, $pedidosServicio);
        }

        $dias = max(1, (int) $this->option('days'));
        $desde = now()->subDays($dias);

        $pagos = Pago::whereNotIn('estado', $this->estadosFinales())
            ->where('created_at', '>=', $desde)
            ->get();

        $pedidos = Pedido::whereNotIn('estado_pago', $this->estadosFinales())
            ->whereNotNull('izipay_order_id')
            ->where('created_at', '>=', $desde)
            ->get();

        $this->info("Pagos por revisar: {$pagos->count()} | Pedidos por revisar: {$pedidos->count()}");

        $resueltos = 0;

        foreach ($pagos as $pago) {
            if ($this->revisar((string) $pago->izipay_order_id, $izipay, fn ($answer) => $pagosServicio->registrar($answer, PagoService::ORIGEN_CONCILIACION))) {
                $resueltos++;
            }
        }

        foreach ($pedidos as $pedido) {
            if ($this->revisar((string) $pedido->izipay_order_id, $izipay, fn ($answer) => $pedidosServicio->registrar($answer, PedidoPagoService::ORIGEN_CONCILIACION))) {
                $resueltos++;
            }
        }

        $this->info("Resueltos a estado final: {$resueltos}");

        return self::SUCCESS;
    }

    private function revisarUnaOrden(string $orderId, IzipayService $izipay, PagoService $pagosServicio, PedidoPagoService $pedidosServicio): int
    {
        $pago = Pago::where('izipay_order_id', $orderId)->first();
        $pedido = Pedido::where('izipay_order_id', $orderId)->first();

        if ($pago === null && $pedido === null) {
            $this->error("No existe ningun pago ni pedido con izipay_order_id={$orderId}.");

            return self::FAILURE;
        }

        $consulta = $izipay->consultarOrden($orderId);

        if (! $consulta['ok']) {
            $this->error('Izipay no respondio a la consulta.');

            return self::FAILURE;
        }

        if (! $consulta['encontrada']) {
            $this->warn('Izipay respondio pero no reconoce esa orden.');

            return self::SUCCESS;
        }

        $resultado = $pago !== null
            ? $pagosServicio->registrar($consulta['answer'], PagoService::ORIGEN_CONCILIACION)
            : $pedidosServicio->registrar($consulta['answer'], PedidoPagoService::ORIGEN_CONCILIACION);

        $estado = $resultado['estado'] ?? null;

        $this->info(sprintf(
            'Orden %s -> %s%s',
            $orderId,
            $estado instanceof EstadoPago ? $estado->value : 'sin cambios',
            isset($resultado['motivo']) ? " ({$resultado['motivo']})" : ''
        ));

        return self::SUCCESS;
    }

    /** @return bool true si quedó en un estado final */
    private function revisar(string $orderId, IzipayService $izipay, \Closure $registrar): bool
    {
        if ($orderId === '') {
            return false;
        }

        $consulta = $izipay->consultarOrden($orderId);

        if (! $consulta['ok'] || ! $consulta['encontrada']) {
            return false;
        }

        $estado = ($registrar($consulta['answer']))['estado'] ?? null;

        return $estado instanceof EstadoPago && $estado->esFinal();
    }

    /** @return array<int, string> */
    private function estadosFinales(): array
    {
        return array_values(array_map(
            fn (EstadoPago $e) => $e->value,
            array_filter(EstadoPago::cases(), fn (EstadoPago $e) => $e->esFinal())
        ));
    }
}
