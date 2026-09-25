<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoPago;
use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Producto;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dashboard + listado de pagos en una sola pantalla (antes eran dos
 * paginas separadas). No expone ninguna accion de escritura sobre pagos:
 * el estado solo lo cambian IzipayController::validar()/ipn() (ver
 * PagoService).
 *
 * Dos filtros conviven aqui y son independientes: "rango" (Hoy/7
 * dias/30 dias/Mes) mueve las metricas y "Ultimos pagos"; el filtro de
 * la tabla completa (estado/desde/hasta) es el que ya existia, para
 * busquedas precisas sin depender del rango.
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

        $datosTabla = $request->validate([
            'estado' => ['nullable', Rule::enum(EstadoPago::class)],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $enRango = fn () => Pago::query()->whereBetween('created_at', [$desde, $hasta]);

        $porEstado = $enRango()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $intentos = (int) $porEstado->sum();
        $pagadosEnRango = (int) ($porEstado[EstadoPago::Pagado->value] ?? 0);
        $rechazadosEnRango = (int) ($porEstado[EstadoPago::Rechazado->value] ?? 0);

        [$desdeHoy, $hastaHoy] = $this->limitesDeRango('hoy');

        $nombresProducto = Producto::pluck('nombre', 'slug');

        $productos = Producto::orderBy('orden')->get()->map(function (Producto $producto) {
            $base = fn () => Pago::where('producto', $producto->slug);

            return (object) [
                'producto' => $producto,
                'ventas' => $base()->where('estado', EstadoPago::Pagado)->count(),
                'ingresos' => (int) $base()->where('estado', EstadoPago::Pagado)->sum('monto'),
                'intentosRecientes' => $base()
                    ->where('created_at', '>=', now()->subHours(self::VENTANA_INTENTOS_RECIENTES_HORAS))
                    ->count(),
            ];
        });

        $pagos = Pago::query()
            ->when($datosTabla['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($datosTabla['desde'] ?? null, fn ($q, $desde) => $q->whereDate('created_at', '>=', $desde))
            ->when($datosTabla['hasta'] ?? null, fn ($q, $hasta) => $q->whereDate('created_at', '<=', $hasta))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.dashboard', [
            'rango' => $rango,
            'fechaLarga' => CarbonImmutable::now(self::ZONA)->locale('es')->translatedFormat('l d \d\e F \d\e Y'),

            'ingresosRango' => (int) $enRango()->where('estado', EstadoPago::Pagado)->sum('monto'),
            'ingresosHoy' => (int) Pago::where('estado', EstadoPago::Pagado)
                ->whereBetween('created_at', [$desdeHoy, $hastaHoy])->sum('monto'),
            'ingresosTotal' => (int) Pago::where('estado', EstadoPago::Pagado)->sum('monto'),

            'intentos' => $intentos,
            'ticketPromedio' => $intentos > 0 ? (int) round($enRango()->avg('monto')) : 0,
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

            // Cuando ya hay un filtro de estado activo (por ejemplo, clic en
            // una fila de "Pagos por estado"), "Ultimos pagos" tambien lo
            // respeta: si no, el resumen de arriba y la tabla de abajo
            // mostrarian cosas distintas para el mismo filtro.
            'ultimosPagos' => $enRango()
                ->when($datosTabla['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(),
            'nombresProducto' => $nombresProducto,

            'productos' => $productos,
            'maxIntentosRecientes' => max(1, (int) $productos->max('intentosRecientes')),
            'totalProductosActivos' => Producto::where('activo', true)->count(),
            'totalProductos' => Producto::count(),

            'pagos' => $pagos,
            'filtros' => $datosTabla,
        ]);
    }

    public function exportarCsv(Request $request): StreamedResponse
    {
        $rango = $this->resolverRango($request);
        [$desde, $hasta] = $this->limitesDeRango($rango);

        $pagos = Pago::whereBetween('created_at', [$desde, $hasta])->orderBy('created_at')->get();
        $nombresProducto = Producto::pluck('nombre', 'slug');
        $nombreArchivo = 'pagos-'.$rango.'-'.CarbonImmutable::now(self::ZONA)->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($pagos, $nombresProducto) {
            // Exporta el correo sin enmascarar: es un archivo para el propio
            // admin (ya autenticado), no la vista en pantalla.
            $salida = fopen('php://output', 'w');
            fputcsv($salida, ['Fecha', 'Producto', 'Email', 'Monto', 'Moneda', 'Estado', 'Order ID']);

            foreach ($pagos as $pago) {
                fputcsv($salida, [
                    $pago->created_at->setTimezone(self::ZONA)->format('Y-m-d H:i'),
                    $nombresProducto[$pago->producto] ?? $pago->producto,
                    $pago->email,
                    number_format($pago->monto / 100, 2),
                    $pago->moneda,
                    $pago->estado->etiqueta(),
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
