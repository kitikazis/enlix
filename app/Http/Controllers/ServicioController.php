<?php

namespace App\Http\Controllers;

use App\Support\Catalogo;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Páginas de servicio. Una sola acción sirve todas las páginas de servicio,
 * leyendo los datos del catálogo (config/servicios.php).
 */
class ServicioController extends Controller
{
    public function show(string $slug): View|RedirectResponse
    {
        $svc = Catalogo::find($slug);

        if ($svc === null) {
            // Servicios retirados del catálogo: 301 al reemplazo más cercano.
            $redirecciones = config('servicios.redirecciones', []);

            abort_unless(array_key_exists($slug, $redirecciones), 404);

            $destino = $redirecciones[$slug];

            return redirect()->to(
                $destino ? route('servicio.show', $destino) : route('inicio').'#servicios',
                301
            );
        }

        return view('servicio', [
            'titulo'          => $svc['title'].' - Enlix',
            'metaDescription' => $svc['lead'],
            'current'         => 'servicios',
            'servicio_actual' => $slug,
            'svc'             => $svc,
        ]);
    }
}
