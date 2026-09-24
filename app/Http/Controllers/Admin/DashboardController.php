<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoPago;
use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Dashboard + listado de pagos en una sola pantalla (antes eran dos
 * paginas separadas). No expone ninguna accion de escritura sobre pagos:
 * el estado solo lo cambian IzipayController::validar()/ipn() (ver
 * PagoService).
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $datos = $request->validate([
            'estado' => ['nullable', Rule::enum(EstadoPago::class)],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $pagados = Pago::where('estado', EstadoPago::Pagado);

        $ingresosTotal = (clone $pagados)->sum('monto');
        $ingresosMes = (clone $pagados)->where('created_at', '>=', now()->startOfMonth())->sum('monto');
        $ingresosHoy = (clone $pagados)->whereDate('created_at', now()->toDateString())->sum('monto');

        $porEstado = Pago::query()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $totalIntentos = $porEstado->sum();
        $totalPagados = $porEstado[EstadoPago::Pagado->value] ?? 0;
        $tasaConversion = $totalIntentos > 0 ? round($totalPagados / $totalIntentos * 100, 1) : 0.0;

        $ingresosPorProducto = Pago::query()
            ->where('estado', EstadoPago::Pagado)
            ->selectRaw('producto, count(*) as ventas, sum(monto) as total')
            ->groupBy('producto')
            ->orderByDesc('total')
            ->get();

        $pagos = Pago::query()
            ->when($datos['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($datos['desde'] ?? null, fn ($q, $desde) => $q->whereDate('created_at', '>=', $desde))
            ->when($datos['hasta'] ?? null, fn ($q, $hasta) => $q->whereDate('created_at', '<=', $hasta))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.dashboard', [
            'ingresosTotal' => $ingresosTotal,
            'ingresosMes' => $ingresosMes,
            'ingresosHoy' => $ingresosHoy,
            'porEstado' => $porEstado,
            'estados' => EstadoPago::cases(),
            'tasaConversion' => $tasaConversion,
            'ingresosPorProducto' => $ingresosPorProducto,
            'pagos' => $pagos,
            'filtros' => $datos,
            'totalProductosActivos' => Producto::where('activo', true)->count(),
            'totalProductos' => Producto::count(),
        ]);
    }
}
