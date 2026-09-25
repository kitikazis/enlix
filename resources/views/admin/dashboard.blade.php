@php
    use App\Enums\EstadoPago;

    $rangos = ['hoy' => 'Hoy', '7d' => '7 días', '30d' => '30 días', 'mes' => 'Este mes'];
@endphp

<x-layouts.admin-dashboard :titulo="'Dashboard - Enlix Admin'" :badge-atencion="$pendientesEnRango + $enVerificacionEnRango ?: null">
    <div x-data="{ mostrarEmail: false }" class="mx-auto flex max-w-6xl flex-col gap-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm text-text-caption">{{ $fechaLarga }}</p>
                <h1 class="text-2xl font-semibold text-text-primary md:text-[28px]">Dashboard</h1>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="grid grid-cols-4 gap-1 rounded-nav border border-border bg-page p-1 md:inline-flex md:items-center" role="group" aria-label="Rango de fechas">
                    @foreach ($rangos as $valor => $etiqueta)
                        <a
                            href="{{ request()->fullUrlWithQuery(['rango' => $valor]) }}"
                            role="button"
                            aria-pressed="{{ $rango === $valor ? 'true' : 'false' }}"
                            class="rounded-[0.5rem] px-2 py-1.5 text-center text-sm font-medium {{ $rango === $valor ? 'bg-card text-text-primary shadow-sm' : 'text-text-secondary hover:text-text-primary' }}"
                        >{{ $etiqueta }}</a>
                    @endforeach
                </div>

                <a
                    href="{{ route('admin.dashboard.exportar', ['rango' => $rango]) }}"
                    class="inline-flex h-11 items-center gap-2 rounded-nav border border-border bg-card px-3 text-sm font-medium text-text-primary hover:bg-page"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 3a1 1 0 011 1v7.586l2.293-2.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 11.586V4a1 1 0 011-1zM4 15a1 1 0 011 1v1h10v-1a1 1 0 112 0v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1a1 1 0 011-1z"/></svg>
                    Exportar CSV
                </a>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-admin.kpi
                label="Ingresos · {{ $rangos[$rango] }}"
                value="S/ {{ number_format($ingresosRango / 100, 2) }}"
                caption="Total histórico S/ {{ number_format($ingresosTotal / 100, 2) }} · Hoy S/ {{ number_format($ingresosHoy / 100, 2) }}"
            />
            <x-admin.kpi
                label="Intentos de pago"
                value="{{ $intentos }}"
                caption="Ticket promedio intentado S/ {{ number_format($ticketPromedio / 100, 2) }}"
            />
            <x-admin.kpi
                label="Tasa de conversión"
                value="{{ $tasaConversion }}%"
                caption="{{ $porEstado[EstadoPago::Pagado->value] ?? 0 }} de {{ $intentos }} intentos terminaron pagados"
            />
            <x-admin.kpi
                label="Tasa de rechazo"
                value="{{ $tasaRechazo }}%"
                value-class="text-danger-strong"
                caption="{{ $rechazadosEnRango }} rechazados por emisor o pasarela"
            />
        </div>

        {{-- Pagos por estado + Requiere tu atención --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <x-admin.card class="lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-text-primary">Pagos por estado</h2>
                    <span class="text-xs text-text-caption">{{ $intentos }} intentos · clic para filtrar</span>
                </div>

                <div class="mb-5 flex h-3.5 gap-[2px] overflow-hidden rounded-full bg-page">
                    @foreach ($estados as $estado)
                        @php $pct = $intentos > 0 ? (($porEstado[$estado->value] ?? 0) / $intentos * 100) : 0; @endphp
                        @if ($pct > 0)
                            <div class="{{ $estado->dotClass() }}" style="width: {{ $pct }}%" title="{{ $estado->etiqueta() }}: {{ round($pct, 1) }}%"></div>
                        @endif
                    @endforeach
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach ($estados as $estado)
                        @php
                            $cantidad = $porEstado[$estado->value] ?? 0;
                            $pct = $intentos > 0 ? round($cantidad / $intentos * 100, 1) : 0.0;
                        @endphp
                        <a
                            href="{{ route('admin.dashboard', array_merge(request()->query(), ['estado' => $estado->value])).'#pagos' }}"
                            class="flex flex-col gap-1 rounded-nav border border-border p-3 text-left hover:bg-page"
                        >
                            <span class="flex items-center gap-1.5 text-sm font-medium text-text-primary">
                                <span class="h-2 w-2 rounded-full {{ $estado->dotClass() }}" aria-hidden="true"></span>
                                {{ $estado->etiqueta() }}
                            </span>
                            @if (! empty($estado->codigosIzipay()))
                                <span class="truncate font-mono text-[11px] text-text-caption" title="{{ implode(' · ', $estado->codigosIzipay()) }}">
                                    {{ implode(' · ', $estado->codigosIzipay()) }}
                                </span>
                            @endif
                            <span class="mt-1 flex items-baseline gap-1.5">
                                <span class="font-mono text-lg font-semibold text-text-primary">{{ $cantidad }}</span>
                                <span class="font-mono text-xs text-text-caption">{{ $pct }}%</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </x-admin.card>

            <x-admin.card title="Requiere tu atención">
                <div class="flex flex-col gap-3">
                    @if ($enVerificacionEnRango > 0 && $masAntiguoEnVerificacion)
                        <a href="{{ route('admin.dashboard', ['estado' => 'en_verificacion']).'#pagos' }}" class="flex items-center justify-between gap-3 rounded-nav bg-verificacion-bg p-3">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-verificacion-text">
                                    {{ $enVerificacionEnRango }} {{ $enVerificacionEnRango === 1 ? 'pago en verificación' : 'pagos en verificación' }}
                                </span>
                                <span class="block truncate text-xs text-verificacion-text/80">
                                    {{ $nombresProducto[$masAntiguoEnVerificacion->producto] ?? $masAntiguoEnVerificacion->producto }}
                                    · S/ {{ number_format($masAntiguoEnVerificacion->monto / 100, 2) }}
                                    · hace {{ $masAntiguoEnVerificacion->created_at->locale('es')->diffForHumans(null, true) }}
                                </span>
                            </span>
                            <span class="shrink-0 rounded-nav bg-accent px-3 py-1.5 text-xs font-medium text-white">Revisar</span>
                        </a>
                    @endif

                    @if ($pendientesEnRango > 0)
                        <a href="{{ route('admin.dashboard', ['estado' => 'pendiente']).'#pagos' }}" class="flex items-center justify-between gap-3 rounded-nav bg-pendiente-bg p-3">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-pendiente-text">{{ $pendientesEnRango }} pagos sin completar</span>
                                <span class="block text-xs text-pendiente-text/80">Iniciados y abandonados en checkout</span>
                            </span>
                            <span class="shrink-0 text-xs font-medium text-pendiente-text underline">Ver</span>
                        </a>
                    @endif

                    @if ($ultimosPruebaCount > 0)
                        <div class="flex items-center justify-between gap-3 rounded-nav bg-page p-3">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-text-primary">Datos de prueba en producción</span>
                                <span class="block text-xs text-text-caption">{{ $ultimosPruebaCount }} de los últimos 10 pagos parecen de prueba</span>
                            </span>
                            <span class="shrink-0 text-xs font-medium text-text-disabled" title="Excluir datos de prueba llega en una fase siguiente">Excluir</span>
                        </div>
                    @endif

                    @if ($enVerificacionEnRango === 0 && $pendientesEnRango === 0 && $ultimosPruebaCount === 0)
                        <p class="text-sm text-text-caption">Todo al día, no hay nada pendiente de revisar.</p>
                    @endif
                </div>
            </x-admin.card>
        </div>

        {{-- Últimos pagos + Productos --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <x-admin.card class="lg:col-span-2" :padded="false">
                <div class="flex items-center justify-between gap-3 border-b border-border px-5 py-4">
                    <h2 class="text-sm font-semibold text-text-primary">Últimos pagos</h2>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="mostrarEmail = !mostrarEmail" class="text-xs font-medium text-accent hover:text-accent-hover">
                            <span x-text="mostrarEmail ? 'Ocultar emails' : 'Mostrar emails'"></span>
                        </button>
                        <a href="{{ '#pagos' }}" class="text-xs font-medium text-accent hover:text-accent-hover">Ver todos →</a>
                    </div>
                </div>

                <ul class="divide-y divide-border">
                    @forelse ($ultimosPagos as $pago)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-text-primary">{{ $nombresProducto[$pago->producto] ?? $pago->producto }}</p>
                                <p class="truncate text-xs text-text-caption">
                                    <span x-show="!mostrarEmail">{{ $pago->emailEnmascarado() }}</span>
                                    <span x-show="mostrarEmail" x-cloak>{{ $pago->email }}</span>
                                    @if ($pago->esPrueba())
                                        <span class="ml-1 rounded border border-dashed border-text-disabled px-1 text-[10px] uppercase tracking-wide text-text-disabled">Prueba</span>
                                    @endif
                                </p>
                                <p class="font-mono text-[11px] text-text-caption">hace {{ $pago->created_at->locale('es')->diffForHumans(null, true) }} · {{ $pago->created_at->setTimezone('America/Lima')->format('d/m H:i') }}</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1.5">
                                <span class="font-mono text-sm font-medium text-text-primary">S/ {{ number_format($pago->monto / 100, 2) }}</span>
                                <x-admin.badge-estado :estado="$pago->estado" />
                            </div>
                        </li>
                    @empty
                        <li class="p-5">
                            <x-admin.empty-state title="No hay pagos en este rango." />
                        </li>
                    @endforelse
                </ul>
            </x-admin.card>

            <x-admin.card title="Productos" :action="$totalProductosActivos.' de '.$totalProductos.' activos'">
                <div class="flex flex-col gap-4">
                    @foreach ($productos as $fila)
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate text-sm font-medium text-text-primary">{{ $fila->producto->nombre }}</p>
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $fila->producto->activo ? 'bg-pagado-bg text-pagado-text' : 'bg-anulado-bg text-anulado-text' }}">
                                    {{ $fila->producto->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>
                            <p class="font-mono text-base font-semibold text-text-primary">S/ {{ number_format($fila->producto->precio_centimos / 100, 2) }}</p>
                            <p class="text-xs text-text-caption">{{ $fila->ventas }} ventas · {{ $fila->intentosRecientes }} intentos recientes</p>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-page">
                                <div class="h-full rounded-full bg-accent" style="width: {{ $fila->intentosRecientes / $maxIntentosRecientes * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach

                    @if ($productos->sum('ventas') === 0)
                        <x-admin.empty-state
                            title="Aún sin ventas confirmadas"
                            description="Los ingresos por producto aparecen con el primer pago CAPTURED."
                        />
                    @endif

                    <a href="{{ route('admin.productos.index') }}" class="flex h-11 items-center justify-center rounded-nav bg-sidebar text-sm font-medium text-white hover:bg-sidebar-active">
                        Gestionar productos
                    </a>
                </div>
            </x-admin.card>
        </div>

        {{-- Todos los pagos (filtro completo) --}}
        <div id="pagos" class="scroll-mt-6">
            <x-admin.card title="Todos los pagos">
                <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
                    <input type="hidden" name="rango" value="{{ $rango }}">
                    <div>
                        <label class="mb-1 block text-xs text-text-caption">Estado</label>
                        <select name="estado" class="h-9 rounded-nav border border-border bg-card px-2 text-sm">
                            <option value="">Todos</option>
                            @foreach ($estados as $estado)
                                <option value="{{ $estado->value }}" @selected(($filtros['estado'] ?? '') === $estado->value)>{{ $estado->etiqueta() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-text-caption">Desde</label>
                        <input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" class="h-9 rounded-nav border border-border bg-card px-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-text-caption">Hasta</label>
                        <input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" class="h-9 rounded-nav border border-border bg-card px-2 text-sm">
                    </div>
                    <button type="submit" class="h-9 rounded-nav bg-accent px-3 text-sm font-medium text-white hover:bg-accent-hover">Filtrar</button>
                    <a href="{{ route('admin.dashboard', ['rango' => $rango]).'#pagos' }}" class="h-9 rounded-nav border border-border px-3 text-sm font-medium leading-9 text-text-secondary">Limpiar</a>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-border text-xs text-text-caption">
                                <th class="py-2 pr-3 font-medium">Fecha</th>
                                <th class="py-2 pr-3 font-medium">Producto</th>
                                <th class="py-2 pr-3 font-medium">Email</th>
                                <th class="py-2 pr-3 text-right font-medium">Monto</th>
                                <th class="py-2 pr-3 font-medium">Estado</th>
                                <th class="py-2 pr-3 font-medium">Tarjeta</th>
                                <th class="py-2 font-medium">Order ID</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse ($pagos as $pago)
                                <tr>
                                    <td class="whitespace-nowrap py-2.5 pr-3 font-mono text-xs text-text-secondary">{{ $pago->created_at->setTimezone('America/Lima')->format('d/m/Y H:i') }}</td>
                                    <td class="whitespace-nowrap py-2.5 pr-3">{{ $nombresProducto[$pago->producto] ?? $pago->producto }}</td>
                                    <td class="max-w-[180px] truncate py-2.5 pr-3 text-text-secondary" title="{{ $pago->email }}">
                                        <span x-show="!mostrarEmail">{{ $pago->emailEnmascarado() }}</span>
                                        <span x-show="mostrarEmail" x-cloak>{{ $pago->email }}</span>
                                    </td>
                                    <td class="whitespace-nowrap py-2.5 pr-3 text-right font-mono">{{ $pago->moneda }} {{ number_format($pago->monto / 100, 2) }}</td>
                                    <td class="whitespace-nowrap py-2.5 pr-3"><x-admin.badge-estado :estado="$pago->estado" /></td>
                                    <td class="whitespace-nowrap py-2.5 pr-3 font-mono text-xs text-text-secondary">{{ $pago->card_brand ? $pago->card_brand.' '.$pago->card_masked_pan : '—' }}</td>
                                    <td class="max-w-[140px] truncate py-2.5 font-mono text-xs text-text-caption" title="{{ $pago->izipay_order_id }}">{{ $pago->izipay_order_id }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8">
                                        <x-admin.empty-state title="No hay pagos con estos filtros." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $pagos->links() }}
                </div>
            </x-admin.card>
        </div>
    </div>
</x-layouts.admin-dashboard>
