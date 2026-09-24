<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoPago;
use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Producto;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
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

        $ultimosPagos = Pago::query()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'producto', 'email', 'monto', 'estado', 'created_at']);

        return view('admin.dashboard', [
            'ingresosTotal' => $ingresosTotal,
            'ingresosMes' => $ingresosMes,
            'ingresosHoy' => $ingresosHoy,
            'porEstado' => $porEstado,
            'estados' => EstadoPago::cases(),
            'tasaConversion' => $tasaConversion,
            'ingresosPorProducto' => $ingresosPorProducto,
            'ultimosPagos' => $ultimosPagos,
            'totalProductosActivos' => Producto::where('activo', true)->count(),
            'totalProductos' => Producto::count(),
        ]);
    }
}
