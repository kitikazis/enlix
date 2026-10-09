<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Finder\Finder;

/**
 * Housekeeping de disco para el hosting compartido (cuota de 1 GB): borra
 * archivos viejos de carpetas que solo deberían tener contenido transitorio,
 * y purga filas de caché ya expiradas en la tabla 'cache' (el store
 * 'database' no las autolimpia como sí hace el driver de sesiones).
 *
 * Las carpetas se recorren con Finder y se ignoran si no existen todavía
 * (p.ej. storage/app/tmp no se crea hasta que algo la use).
 */
class LimpiarTemporales extends Command
{
    protected $signature = 'app:limpiar-temporales {--dias=7 : Antigüedad mínima en días para borrar}';

    protected $description = 'Borra archivos temporales/exports viejos y purga caché expirada en BD';

    /** Carpetas con contenido transitorio o regenerable, nunca código ni datos de negocio. */
    private const CARPETAS = [
        'storage/app/tmp',
        'storage/framework/cache/data',
    ];

    public function handle(): int
    {
        $dias = (int) $this->option('dias');
        $limite = now()->subDays($dias);

        foreach (self::CARPETAS as $carpeta) {
            $this->borrarViejos(base_path($carpeta), $limite, $dias);
        }

        $this->purgarCacheExpirada();

        return self::SUCCESS;
    }

    private function borrarViejos(string $ruta, \Illuminate\Support\Carbon $limite, int $dias): void
    {
        if (! is_dir($ruta)) {
            $this->line("Omitido (no existe): {$ruta}");

            return;
        }

        $finder = (new Finder)
            ->in($ruta)
            ->files()
            ->ignoreDotFiles(true)
            ->date('before '.$limite->toDateTimeString());

        $borrados = 0;

        foreach ($finder as $archivo) {
            @unlink($archivo->getRealPath());
            $borrados++;
        }

        $this->info("{$ruta}: {$borrados} archivo(s) de más de {$dias} día(s) borrados.");
    }

    private function purgarCacheExpirada(): void
    {
        $tabla = config('cache.stores.database.table', 'cache');

        $borradas = DB::table($tabla)
            ->where('expiration', '<', now()->getTimestamp())
            ->delete();

        $this->info("Caché expirada: {$borradas} fila(s) borradas de '{$tabla}'.");
    }
}
