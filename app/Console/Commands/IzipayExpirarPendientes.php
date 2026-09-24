<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EstadoPago;
use App\Models\Pago;
use App\Services\IzipayService;
use App\Services\PagoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cierra los pagos que llevan más de 24h en 'pendiente'.
 *
 * Nunca expira a ciegas: antes le pregunta a Izipay por el estado real de la
 * orden, porque un pago pudo completarse aunque el IPN no llegara. Solo se
 * marca 'expirado' cuando Izipay confirma que esa orden no existe (el
 * comprador abrió el checkout y nunca intentó pagar).
 *
 * Los pagos 'en_verificacion' NO se tocan aquí: esos ya tuvieron un intento
 * real y los resuelve izipay:conciliar-pendientes.
 */
class IzipayExpirarPendientes extends Command
{
    protected $signature = 'izipay:expirar-pendientes';

    protected $description = "Cierra los pagos 'pendiente' de más de 24 horas, consultando antes su estado real en Izipay";

    public function handle(IzipayService $izipay, PagoService $pagos): int
    {
        $vencidos = Pago::where('estado', EstadoPago::Pendiente)
            ->where('created_at', '<', now()->subDay())
            ->get();

        $expirados = 0;
        $recuperados = 0;

        foreach ($vencidos as $pago) {
            $consulta = $izipay->consultarOrden((string) $pago->izipay_order_id);

            if (! $consulta['ok']) {
                // Izipay no respondió: se reintenta en la siguiente corrida.
                continue;
            }

            if ($consulta['encontrada']) {
                $resultado = $pagos->registrar($consulta['answer'], PagoService::ORIGEN_CONCILIACION);

                if (($resultado['estado'] ?? null) !== EstadoPago::Pendiente) {
                    $recuperados++;
                }

                continue;
            }

            if ($this->expirar($pago)) {
                $expirados++;
            }
        }

        $this->info("Revisados: {$vencidos->count()} | expirados: {$expirados} | resueltos con Izipay: {$recuperados}");

        return self::SUCCESS;
    }

    private function expirar(Pago $pago): bool
    {
        return (bool) DB::transaction(function () use ($pago): bool {
            $fresco = Pago::where('id', $pago->id)->lockForUpdate()->first();

            if ($fresco === null || ! $fresco->estado->puedeTransicionarA(EstadoPago::Expirado)) {
                return false;
            }

            $anterior = $fresco->estado;
            $fresco->estado = EstadoPago::Expirado;
            $fresco->save();

            Log::info('Izipay: pago expirado sin intento de cobro', [
                'pago_id' => $fresco->id,
                'izipay_order_id' => $fresco->izipay_order_id,
                'origen' => 'expiracion',
                'estado_anterior' => $anterior->value,
                'estado_nuevo' => EstadoPago::Expirado->value,
            ]);

            return true;
        });
    }
}
