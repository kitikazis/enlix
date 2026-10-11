<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoReclamo;
use App\Http\Controllers\Controller;
use App\Models\Reclamo;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReclamosController extends Controller
{
    public function index(): View
    {
        return view('admin.reclamos.index', [
            'titulo' => 'Reclamos - Enlix Admin',
            'reclamos' => Reclamo::orderByDesc('created_at')->paginate(20),
            // Distinto de la pagina actual de $reclamos (paginado): el total
            // real de pendientes, sin importar en que pagina este el admin.
            'pendientes' => Reclamo::where('estado', EstadoReclamo::Pendiente)->count(),
        ]);
    }

    public function show(Reclamo $reclamo): View
    {
        return view('admin.reclamos.show', [
            'titulo' => 'Reclamo '.$reclamo->numero_correlativo.' - Enlix Admin',
            'reclamo' => $reclamo,
        ]);
    }

    public function atender(Reclamo $reclamo): RedirectResponse
    {
        $reclamo->update(['estado' => EstadoReclamo::Atendido]);

        return back()->with('status', 'Reclamo marcado como atendido.');
    }
}
