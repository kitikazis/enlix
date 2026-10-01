<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoPago;
use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Producto;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dashboard de metricas + "/admin/pagos" (listado completo con filtros, ver
 * pagos()). No expone ninguna accion de escritura sobre pagos: el estado
 * solo lo cambian IzipayController::validar()/ipn() (ver PagoService).
 *
 * Dos filtros conviven en el dashboard y son independientes: "rango" (Hoy/7
 * dias/30 dias/Mes) mueve las metricas y "Ultimos pagos"; "estado" resalta
 * una tarjeta de "Pagos por estado" y filtra "Ultimos pagos" sin moverse de
 * la pagina. El filtro con fechas propias (desde/hasta) vive solo en
 * /admin/pagos.
 *
 * "excluir_prueba" (activo por defecto) es el toggle global de datos de
 * prueba: se aplica a todas las metricas y listados de ambas pantallas via
 * Pago::scopeEsPrueba().
 */
class DashboardController extends Controller
{
    private const ZONA = 'America/Lima';

    private const RANGOS = ['hoy', '7d', '30d', 'mes'];

    private const VENTANA_INTENTOS_RECIENTES_HORAS = 24;

    public function index(Request $request): View
    {
        $rango = $this->resolverRango($request);
        [$desde, $hasta] = $this->limitesDeRango($rango);
        $excluirPrueba = $request->boolean('excluir_prueba', true);

        $filtros = $request->validate([
            'estado' => ['nullable', Rule::enum(EstadoPago::class)],
        ]);
        $estadoFiltro = $filtros['estado'] ?? null;

        $enRango = fn () => Pago::query()
            ->whereBetween('created_at', [$desde, $hasta])
            ->when($excluirPrueba, fn (Builder $q) => $q->whereNot(fn (Builder $q2) => $q2->esPrueba()));

        $porEstado = $enRango()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $intentos = (int) $porEstado->sum();
        $pagadosEnRango = (int) ($porEstado[EstadoPago::Pagado->value] ?? 0);
        $rechazadosEnRango = (int) ($porEstado[EstadoPago::Rechazado->value] ?? 0);

        [$desdeHoy, $hastaHoy] = $this->limitesDeRango('hoy');

        $nombresProducto = Producto::pluck('nombre', 'slug');

        $productos = Producto::orderBy('orden')->get()->map(function (Producto $producto) use ($excluirPrueba) {
            $base = fn () => Pago::where('producto', $producto->slug)
                ->when($excluirPrueba, fn (Builder $q) => $q->whereNot(fn (Builder $q2) => $q2->esPrueba()));

            return (object) [
                'producto' => $producto,
                'ventas' => $base()->where('estado', EstadoPago::Pagado)->count(),
                'ingresos' => (int) $base()->where('estado', EstadoPago::Pagado)->sum('monto'),
                'intentosRecientes' => $base()
                    ->where('created_at', '>=', now()->subHours(self::VENTANA_INTENTOS_RECIENTES_HORAS))
                    ->count(),
            ];
        });

        return view('admin.dashboard', [
            'rango' => $rango,
            'fechaLarga' => CarbonImmutable::now(self::ZONA)->locale('es')->translatedFormat('l d \d\e F \d\e Y'),
            'excluirPrueba' => $excluirPrueba,
            'estadoFiltro' => $estadoFiltro,

            'ingresosRango' => (int) $enRango()->where('estado', EstadoPago::Pagado)->sum('monto'),
            'ingresosHoy' => (int) Pago::where('estado', EstadoPago::Pagado)
                ->whereBetween('created_at', [$desdeHoy, $hastaHoy])
                ->when($excluirPrueba, fn (Builder $q) => $q->whereNot(fn (Builder $q2) => $q2->esPrueba()))
                ->sum('monto'),
            'ingresosTotal' => (int) Pago::where('estado', EstadoPago::Pagado)
                ->when($excluirPrueba, fn (Builder $q) => $q->whereNot(fn (Builder $q2) => $q2->esPrueba()))
                ->sum('monto'),

            'intentos' => $intentos,
            // avg() puede devolver string segun el driver de base de datos;
            // round() con strict_types exige int|float.
            'ticketPromedio' => $intentos > 0 ? (int) round((float) $enRango()->avg('monto')) : 0,
            'tasaConversion' => $intentos > 0 ? round($pagadosEnRango / $intentos * 100, 1) : 0.0,
            'tasaRechazo' => $intentos > 0 ? round($rechazadosEnRango / $intentos * 100, 1) : 0.0,
            'rechazadosEnRango' => $rechazadosEnRango,

            'porEstado' => $porEstado,
            'estados' => EstadoPago::cases(),

            'pendientesEnRango' => (int) ($porEstado[EstadoPago::Pendiente->value] ?? 0),
            'enVerificacionEnRango' => (int) ($porEstado[EstadoPago::EnVerificacion->value] ?? 0),
            'masAntiguoEnVerificacion' => $enRango()
                ->where('estado', EstadoPago::EnVerificacion)
                ->oldest('created_at')
                ->first(),
            'ultimosPruebaCount' => Pago::query()->latest('created_at')->limit(10)->get()
                ->filter(fn (Pago $pago) => $pago->esPrueba())->count(),

            // Cuando ya hay un filtro de estado activo (clic en una tarjeta
            // de "Pagos por estado"), "Ultimos pagos" tambien lo respeta.
            'ultimosPagos' => $enRango()
                ->when($estadoFiltro, fn ($q, $estado) => $q->where('estado', $estado))
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(),
            'nombresProducto' => $nombresProducto,

            'productos' => $productos,
            'totalProductosActivos' => Producto::where('activo', true)->count(),
            'totalProductos' => Producto::count(),
        ]);
    }

    /** "/admin/pagos": listado completo con filtros propios (estado/desde/hasta). */
    public function pagos(Request $request): View
    {
        $excluirPrueba = $request->boolean('excluir_prueba', true);

        $filtros = $request->validate([
            'estado' => ['nullable', Rule::enum(EstadoPago::class)],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $pagos = Pago::query()
            ->when($excluirPrueba, fn (Builder $q) => $q->whereNot(fn (Builder $q2) => $q2->esPrueba()))
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($filtros['desde'] ?? null, fn ($q, $desde) => $q->whereDate('created_at', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($q, $hasta) => $q->whereDate('created_at', '<=', $hasta))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.pagos', [
            'pagos' => $pagos,
            'nombresProducto' => Producto::pluck('nombre', 'slug'),
            'estados' => EstadoPago::cases(),
            'filtros' => $filtros,
            'excluirPrueba' => $excluirPrueba,
        ]);
    }

    public function exportarCsv(Request $request): StreamedResponse
    {
        $rango = $this->resolverRango($request);
        [$desde, $hasta] = $this->limitesDeRango($rango);
        $excluirPrueba = $request->boolean('excluir_prueba', true);

        $pagos = Pago::whereBetween('created_at', [$desde, $hasta])
            ->when($excluirPrueba, fn (Builder $q) => $q->whereNot(fn (Builder $q2) => $q2->esPrueba()))
            ->orderBy('created_at')
            ->get();
        $nombresProducto = Producto::pluck('nombre', 'slug');
        $nombreArchivo = 'pagos-'.$rango.'-'.CarbonImmutable::now(self::ZONA)->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($pagos, $nombresProducto) {
            // Exporta el correo sin enmascarar: es un archivo para el propio
            // admin (ya autenticado), no la vista en pantalla.
            $salida = fopen('php://output', 'w');
            fputcsv($salida, ['Fecha', 'Producto', 'Email', 'Monto', 'Moneda', 'Estado', 'Método', 'Order ID']);

            foreach ($pagos as $pago) {
                fputcsv($salida, [
                    $pago->created_at->setTimezone(self::ZONA)->format('Y-m-d H:i'),
                    $nombresProducto[$pago->producto] ?? $pago->producto,
                    $pago->email,
                    number_format($pago->monto / 100, 2),
                    $pago->moneda,
                    $pago->estado->etiqueta(),
                    $pago->metodo_pago?->etiqueta() ?? '',
                    $pago->izipay_order_id,
                ]);
            }

            fclose($salida);
        }, $nombreArchivo, ['Content-Type' => 'text/csv']);
    }

    private function resolverRango(Request $request): string
    {
        $rango = (string) $request->query('rango', '30d');

        return in_array($rango, self::RANGOS, true) ? $rango : '30d';
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function limitesDeRango(string $rango): array
    {
        $ahora = CarbonImmutable::now(self::ZONA);

        [$desde, $hasta] = match ($rango) {
            'hoy' => [$ahora->startOfDay(), $ahora->endOfDay()],
            '7d' => [$ahora->subDays(6)->startOfDay(), $ahora->endOfDay()],
            'mes' => [$ahora->startOfMonth(), $ahora->endOfDay()],
            default => [$ahora->subDays(29)->startOfDay(), $ahora->endOfDay()],
        };

        return [$desde->setTimezone('UTC'), $hasta->setTimezone('UTC')];
    }
}
