<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EstadoPago;
use App\Models\Pago;
use App\Services\IzipayService;
use App\Services\PagoService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Cierra el hueco de las IPN que nunca llegan.
 *
 * El estado de un pago depende de que Izipay nos avise (IPN). Si esa
 * notificación se pierde (caída nuestra, error de red, regla mal
 * configurada), un pago realmente cobrado se queda 'pendiente' o
 * 'en_verificacion' para siempre. Este comando le pregunta a Izipay por
 * esos casos y aplica el mismo mapeo de estados que el IPN.
 *
 * Ventanas: se espera 20 minutos antes de tocar un pago (el comprador puede
 * estar todavía en el formulario). 'pendiente' se revisa hasta las 24h, que
 * es cuando izipay:expirar-pendientes se encarga; 'en_verificacion' hasta
 * las 72h, porque ahí ya hubo un intento real y puede tardar en resolverse.
 */
class IzipayConciliarPendientes extends Command
{
    protected $signature = 'izipay:conciliar-pendientes';

    protected $description = 'Consulta en Izipay los pagos sin estado final y los actualiza (cubre IPN perdidas)';

    private const ESPERA_MINUTOS = 20;

    private const LIMITE_PENDIENTE_HORAS = 24;

    private const LIMITE_VERIFICACION_HORAS = 72;

    /** A partir de aquí se asume que la IPN no va a llegar y se avisa. */
    private const ALERTA_HORAS = 1;

    public function handle(IzipayService $izipay, PagoService $pagos): int
    {
        $porRevisar = $this->porRevisar();

        if ($porRevisar->isEmpty()) {
            $this->info('No hay pagos por conciliar.');

            return self::SUCCESS;
        }

        $this->alertarSiHayDemoras($porRevisar);

        $resueltos = 0;
        $sinRespuesta = 0;

        foreach ($porRevisar as $pago) {
            $consulta = $izipay->consultarOrden((string) $pago->izipay_order_id);

            if (! $consulta['ok']) {
                // Izipay no respondió: se reintenta en la próxima corrida.
                $sinRespuesta++;

                continue;
            }

            if (! $consulta['encontrada']) {
                // La orden no existe en Izipay: la expiración se encarga a las 24h.
                continue;
            }

            // Mismo camino que el IPN: bloquea la fila, revalida monto/moneda
            // y aplica EstadoPago::desdeRespuestaIzipay(). Si la IPN llegó
            // justo ahora, quien tome el lock primero gana y el otro ve el
            // estado ya final y no lo toca.
            $resultado = $pagos->registrar($consulta['answer'], PagoService::ORIGEN_CONCILIACION);
            $estado = $resultado['estado'] ?? null;

            if ($estado instanceof EstadoPago && $estado->esFinal()) {
                $resueltos++;
            }
        }

        $this->info("Revisados: {$porRevisar->count()} | resueltos: {$resueltos} | sin respuesta de Izipay: {$sinRespuesta}");

        return self::SUCCESS;
    }

    /** @return Collection<int, Pago> */
    private function porRevisar(): Collection
    {
        $desde = now()->subMinutes(self::ESPERA_MINUTOS);

        return Pago::query()
            ->where('created_at', '<', $desde)
            ->where(function ($query) {
                $query->where(function ($pendientes) {
                    $pendientes->where('estado', EstadoPago::Pendiente)
                        ->where('created_at', '>', now()->subHours(self::LIMITE_PENDIENTE_HORAS));
                })->orWhere(function ($enVerificacion) {
                    $enVerificacion->where('estado', EstadoPago::EnVerificacion)
                        ->where('created_at', '>', now()->subHours(self::LIMITE_VERIFICACION_HORAS));
                });
            })
            ->orderBy('created_at')
            ->get();
    }

    /** @param  Collection<int, Pago>  $porRevisar */
    private function alertarSiHayDemoras(Collection $porRevisar): void
    {
        $demorados = $porRevisar->filter(
            fn (Pago $pago) => $pago->created_at->lt(now()->subHours(self::ALERTA_HORAS))
        );

        if ($demorados->isEmpty()) {
            return;
        }

        // Monitoreo mínimo: si esto aparece seguido, la regla de notificación
        // de Izipay puede estar caída o mal configurada.
        Log::warning('Izipay: hay pagos sin resolver mas de una hora despues del intento', [
            'total' => $demorados->count(),
            'pago_id_mas_antiguo' => $demorados->first()->id,
            'izipay_order_id_mas_antiguo' => $demorados->first()->izipay_order_id,
        ]);
    }
}
