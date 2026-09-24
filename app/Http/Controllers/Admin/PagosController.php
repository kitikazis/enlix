<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Panel de solo lectura para revisar pagos sin entrar a phpMyAdmin.
 * No expone ninguna accion de escritura: el estado de un pago solo lo
 * cambian IzipayController::validar()/ipn() (ver PagoService).
 */
class PagosController extends Controller
{
    private const ESTADOS_VALIDOS = ['pendiente', 'pagado', 'rechazado', 'expirado'];

    public function index(Request $request): View
    {
        $datos = $request->validate([
            'estado' => ['nullable', 'string', 'in:'.implode(',', self::ESTADOS_VALIDOS)],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $pagos = Pago::query()
            ->when($datos['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($datos['desde'] ?? null, fn ($q, $desde) => $q->whereDate('created_at', '>=', $desde))
            ->when($datos['hasta'] ?? null, fn ($q, $hasta) => $q->whereDate('created_at', '<=', $hasta))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $resumen = Pago::query()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return view('admin.pagos.index', [
            'pagos' => $pagos,
            'resumen' => $resumen,
            'estados' => self::ESTADOS_VALIDOS,
            'filtros' => $datos,
        ]);
    }
}
