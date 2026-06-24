<?php

namespace App\Http\Controllers;

use App\Support\Catalogo;
use Illuminate\View\View;

/**
 * Páginas de servicio. Una sola acción sirve las 15 páginas de servicio,
 * leyendo los datos del catálogo (config/servicios.php).
 */
class ServicioController extends Controller
{
    public function show(string $slug): View
    {
        $svc = Catalogo::find($slug);

        abort_if($svc === null, 404);

        return view('servicio', [
            'titulo'          => $svc['title'].' - Enlix',
            'current'         => 'servicios',
            'servicio_actual' => $slug,
            'svc'             => $svc,
        ]);
    }
}
