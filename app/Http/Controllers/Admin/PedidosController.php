<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoPago;
use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Listado + detalle de pedidos (carrito multi-producto). Es de solo
 * lectura, igual que Admin\DashboardController con `pagos`: el estado de
 * un pedido solo lo cambian CheckoutController::validar()/ipn() (ver
 * PedidoPagoService), nunca una accion manual del admin aqui.
 */
class PedidosController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'estado' => ['nullable', Rule::enum(EstadoPago::class)],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'cliente' => ['nullable', 'string', 'max:150'],
        ]);

        $pedidos = Pedido::query()
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('estado_pago', $estado))
            ->when($filtros['desde'] ?? null, fn ($q, $desde) => $q->whereDate('created_at', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($q, $hasta) => $q->whereDate('created_at', '<=', $hasta))
            ->when($filtros['cliente'] ?? null, function ($q, $cliente) {
                $q->where(fn ($qq) => $qq->where('nombre_cliente', 'like', "%{$cliente}%")
                    ->orWhere('email', 'like', "%{$cliente}%")
                    ->orWhere('codigo', 'like', "%{$cliente}%"));
            })
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.pedidos.index', [
            'pedidos' => $pedidos,
            'estados' => EstadoPago::cases(),
            'filtros' => $filtros,
            'totalPedidos' => Pedido::count(),
            'pagadosCount' => Pedido::where('estado_pago', EstadoPago::Pagado)->count(),
        ]);
    }

    public function show(Pedido $pedido): View
    {
        $pedido->load('items.producto');

        return view('admin.pedidos.show', [
            'pedido' => $pedido,
        ]);
    }
}
