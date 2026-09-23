<?php

namespace App\Console\Commands;

use App\Models\Pago;
use Illuminate\Console\Command;

/**
 * Marca como 'expirado' los pagos que llevan más de 24h en 'pendiente'
 * (el cliente abandonó el checkout o el pago nunca se confirmó).
 */
class IzipayExpirarPendientes extends Command
{
    protected $signature = 'izipay:expirar-pendientes';

    protected $description = "Marca como 'expirado' los pagos 'pendiente' con más de 24 horas de antigüedad";

    public function handle(): int
    {
        $afectados = Pago::where('estado', 'pendiente')
            ->where('created_at', '<', now()->subDay())
            ->update(['estado' => 'expirado']);

        $this->info("Pagos marcados como 'expirado': {$afectados}");

        return self::SUCCESS;
    }
}
