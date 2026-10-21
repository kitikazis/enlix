<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoReclamo;
use App\Http\Controllers\Controller;
use App\Models\Reclamo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    /**
     * Exige registrar la respuesta dada al consumidor: "atendido" sin
     * constancia de qué se respondió ni cuándo no sirve para demostrar el
     * cumplimiento del plazo legal. Un reclamo ya atendido no se reabre.
     */
    public function atender(Request $request, Reclamo $reclamo): RedirectResponse
    {
        if ($reclamo->estado === EstadoReclamo::Atendido) {
            return back()->with('status', 'Este reclamo ya estaba atendido.');
        }

        $datos = $request->validate([
            'respuesta' => ['required', 'string', 'min:10', 'max:5000'],
        ], [
            'respuesta.required' => 'Escribe la respuesta que se dio al consumidor.',
            'respuesta.min' => 'La respuesta debe tener al menos 10 caracteres.',
            'respuesta.max' => 'La respuesta no puede pasar de 5000 caracteres.',
        ]);

        $reclamo->update([
            'estado' => EstadoReclamo::Atendido,
            'respuesta' => trim($datos['respuesta']),
            'respondido_en' => now(),
        ]);

        return back()->with('status', 'Reclamo marcado como atendido.');
    }
}
