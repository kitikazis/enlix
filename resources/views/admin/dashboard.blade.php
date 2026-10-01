@php
    use App\Enums\EstadoPago;
    use App\Support\Dinero;

    $rangos = ['hoy' => 'Hoy', '7d' => '7 días', '30d' => '30 días', 'mes' => 'Este mes'];
    $conversionAlerta = $tasaConversion == 0.0 ? 'text-danger-strong' : ($tasaConversion < 20 ? 'text-pendiente-text' : 'text-text-primary');
    $rechazoAlerta = $tasaRechazo > 15 ? 'text-danger-strong' : ($tasaRechazo > 5 ? 'text-pendiente-text' : 'text-text-primary');
@endphp

<x-layouts.admin-dashboard :titulo="'Dashboard - Enlix Admin'" :badge-atencion="$pendientesEnRango + $enVerificacionEnRango ?: null" :sin-scroll="true">
    <div class="mx-auto flex w-full max-w-[1920px] flex-col gap-3 md:grid md:h-full md:min-h-0 md:grid-rows-[auto_auto_1fr_1fr] md:gap-3 lg:gap-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-[clamp(0.75rem,0.9vh,0.875rem)] text-text-caption">{{ $fechaLarga }}</p>
                <h1 class="text-[clamp(1.25rem,1.6vw,1.75rem)] font-semibold text-text-primary">Dashboard</h1>
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
                    href="{{ request()->fullUrlWithQuery(['excluir_prueba' => $excluirPrueba ? '0' : '1']) }}"
                    aria-pressed="{{ $excluirPrueba ? 'true' : 'false' }}"
                    title="Excluir pagos de prueba (@example.com o montos ≤ S/ 1.00) de los KPIs y listas"
                    class="inline-flex h-11 items-center gap-2 rounded-nav border px-3 text-sm font-medium {{ $excluirPrueba ? 'border-accent bg-accent/10 text-accent' : 'border-border bg-card text-text-secondary hover:bg-page' }}"
                >
                    <span class="flex h-4 w-7 shrink-0 items-center rounded-full px-0.5 transition-colors {{ $excluirPrueba ? 'justify-end bg-accent' : 'justify-start bg-border' }}">
                        <span class="h-3 w-3 rounded-full bg-white shadow"></span>
                    </span>
                    Excluir datos de prueba
                </a>

                <a
                    href="{{ route('admin.dashboard.exportar', ['rango' => $rango, 'excluir_prueba' => $excluirPrueba ? '1' : '0']) }}"
                    class="inline-flex h-11 items-center gap-2 rounded-nav border border-border bg-card px-3 text-sm font-medium text-text-primary hover:bg-page"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 3a1 1 0 011 1v7.586l2.293-2.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 11.586V4a1 1 0 011-1zM4 15a1 1 0 011 1v1h10v-1a1 1 0 112 0v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1a1 1 0 011-1z"/></svg>
                    Exportar CSV
                </a>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-admin.kpi
                label="Ingresos · {{ $rangos[$rango] }}"
                value="{{ Dinero::soles($ingresosRango) }}"
                caption="Total histórico {{ Dinero::soles($ingresosTotal) }} · Hoy {{ Dinero::soles($ingresosHoy) }}"
            />
            <x-admin.kpi
                label="Intentos de pago · {{ $rangos[$rango] }}"
                value="{{ $intentos }}"
                caption="Ticket promedio intentado {{ Dinero::soles($ticketPromedio) }}"
            />
            <x-admin.kpi
                label="Tasa de conversión · {{ $rangos[$rango] }}"
                value="{{ $tasaConversion }}%"
                value-class="{{ $conversionAlerta }}"
                caption="{{ $porEstado[EstadoPago::Pagado->value] ?? 0 }} de {{ $intentos }} intentos terminaron pagados"
            />
            <x-admin.kpi
                label="Tasa de rechazo · {{ $rangos[$rango] }}"
                value="{{ $tasaRechazo }}%"
                value-class="{{ $rechazoAlerta }}"
                caption="{{ $rechazadosEnRango }} rechazados por emisor o pasarela"
            />
        </div>

        {{-- Pagos por estado + Requiere tu atención --}}
        <div class="grid grid-cols-1 gap-3 md:min-h-0 md:grid-rows-2 lg:grid-rows-1 lg:grid-cols-3 lg:gap-4">
            <x-admin.card class="md:min-h-0 lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-text-primary">Pagos por estado</h2>
                    <span class="text-xs text-text-caption">{{ $intentos }} intentos · clic para filtrar</span>
                </div>

                <div class="mb-5 flex h-3.5 gap-[2px] overflow-hidden rounded-full bg-page">
                    @foreach ($estados as $estado)
                        @php
                            $cantidadBar = $porEstado[$estado->value] ?? 0;
                            $pctBar = $intentos > 0 ? ($cantidadBar / $intentos * 100) : 0;
                        @endphp
                        @if ($pctBar > 0)
                            <div class="{{ $estado->dotClass() }}" style="width: {{ $pctBar }}%" title="{{ $estado->etiqueta() }}: {{ $cantidadBar }} ({{ round($pctBar, 1) }}%)"></div>
                        @endif
                    @endforeach
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach ($estados as $estado)
                        @php
                            $cantidad = $porEstado[$estado->value] ?? 0;
                            $pct = $intentos > 0 ? round($cantidad / $intentos * 100, 1) : 0.0;
                            $seleccionado = $estadoFiltro === $estado->value;
                            $queryTile = $seleccionado
                                ? collect(request()->query())->except('estado')->all()
                                : array_merge(request()->query(), ['estado' => $estado->value]);
                            $titleTile = $estado->etiqueta().(empty($estado->codigosIzipay()) ? '' : ' · '.implode(' · ', $estado->codigosIzipay()));
                        @endphp
                        <a
                            href="{{ route('admin.dashboard', $queryTile) }}"
                            aria-pressed="{{ $seleccionado ? 'true' : 'false' }}"
                            title="{{ $titleTile }}"
                            class="flex cursor-pointer flex-col gap-1 rounded-nav border p-3 text-left transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 {{ $seleccionado ? $estado->seleccionadoClasses() : 'border-border hover:border-text-disabled hover:shadow-sm' }}"
                        >
                            <span class="flex items-center gap-1.5 text-sm font-medium text-text-primary">
                                <span class="h-2 w-2 rounded-full {{ $estado->dotClass() }}" aria-hidden="true"></span>
                                {{ $estado->etiqueta() }}
                            </span>
                            <span class="mt-1 flex items-baseline gap-1.5">
                                <span class="font-mono text-lg font-semibold text-text-primary">{{ $cantidad }}</span>
                                <span class="font-mono text-xs text-text-caption">{{ $pct }}%</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </x-admin.card>

            <x-admin.card title="Requiere tu atención" class="self-start md:min-h-0">
                <div class="flex flex-col gap-3">
                    @if ($enVerificacionEnRango > 0 && $masAntiguoEnVerificacion)
                        <div class="flex items-center justify-between gap-3 rounded-nav bg-verificacion-bg p-3">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-verificacion-text">
                                    {{ trans_choice('1 pago en verificación|:count pagos en verificación', $enVerificacionEnRango) }}
                                </span>
                                <span class="block truncate text-xs text-verificacion-text/80">
                                    {{ $nombresProducto[$masAntiguoEnVerificacion->producto] ?? $masAntiguoEnVerificacion->producto }}
                                    · {{ Dinero::soles($masAntiguoEnVerificacion->monto) }}
                                    · {{ $masAntiguoEnVerificacion->tiempoRelativo() }}
                                </span>
                            </span>
                            <a
                                href="{{ route('admin.pagos', ['estado' => 'en_verificacion', 'excluir_prueba' => $excluirPrueba ? '1' : '0']) }}"
                                class="inline-flex h-9 shrink-0 items-center justify-center rounded-nav border border-verificacion-dot px-3 text-sm font-medium text-verificacion-text hover:bg-card"
                            >Revisar</a>
                        </div>
                    @endif

                    @if ($pendientesEnRango > 0)
                        <div class="flex items-center justify-between gap-3 rounded-nav bg-pendiente-bg p-3">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-pendiente-text">{{ trans_choice('1 pago sin completar|:count pagos sin completar', $pendientesEnRango) }}</span>
                                <span class="block text-xs text-pendiente-text/80">Iniciados y abandonados en checkout</span>
                            </span>
                            <a
                                href="{{ route('admin.pagos', ['estado' => 'pendiente', 'excluir_prueba' => $excluirPrueba ? '1' : '0']) }}"
                                class="inline-flex h-9 shrink-0 items-center justify-center rounded-nav border border-pendiente-dot px-3 text-sm font-medium text-pendiente-text hover:bg-card"
                            >Ver</a>
                        </div>
                    @endif

                    @if (! $excluirPrueba && $ultimosPruebaCount > 0)
                        <div class="flex items-center justify-between gap-3 rounded-nav bg-page p-3">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-text-primary">Datos de prueba en producción</span>
                                <span class="block text-xs text-text-caption">
                                    {{ trans_choice('1 de los últimos 10 pagos parece de prueba|:count de los últimos 10 pagos parecen de prueba', $ultimosPruebaCount) }}
                                </span>
                            </span>
                            <a
                                href="{{ request()->fullUrlWithQuery(['excluir_prueba' => '1']) }}"
                                class="inline-flex h-9 shrink-0 items-center justify-center rounded-nav border border-border px-3 text-sm font-medium text-text-secondary hover:bg-card"
                            >Excluir</a>
                        </div>
                    @endif

                    @if ($enVerificacionEnRango === 0 && $pendientesEnRango === 0 && ($excluirPrueba || $ultimosPruebaCount === 0))
                        <p class="text-sm text-text-caption">Todo al día, no hay nada pendiente de revisar.</p>
                    @endif
                </div>
            </x-admin.card>
        </div>

        {{-- Últimos pagos + Productos --}}
        <div class="grid grid-cols-1 gap-3 md:min-h-0 md:grid-rows-2 lg:grid-rows-1 lg:grid-cols-3 lg:gap-4">
            <x-admin.card class="md:min-h-0 lg:col-span-2" :padded="false">
                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-border px-[clamp(0.875rem,1.5vh,1.25rem)] py-[clamp(0.625rem,1.25vh,1rem)]">
                    <h2 class="text-sm font-semibold text-text-primary">Últimos pagos</h2>
                    <a href="{{ route('admin.pagos', ['excluir_prueba' => $excluirPrueba ? '1' : '0']) }}" class="text-xs font-medium text-accent hover:text-accent-hover">Ver todos →</a>
                </div>

                <ul
                    x-init="actualizarScrollFade($el)" @scroll="actualizarScrollFade($el)" @resize.window="actualizarScrollFade($el)"
                    class="dash-scroll divide-y divide-border md:min-h-0 md:flex-1 md:overflow-y-auto"
                >
                    @forelse ($ultimosPagos as $pago)
                        <li class="flex items-center justify-between gap-3 px-[clamp(0.875rem,1.5vh,1.25rem)] py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-text-primary">{{ $nombresProducto[$pago->producto] ?? $pago->producto }}</p>
                                <p class="truncate text-xs text-text-caption" title="{{ $pago->email }}">
                                    {{ $pago->emailEnmascarado() }}
                                    @if ($pago->esPrueba())
                                        <span class="ml-1 rounded border border-dashed border-text-disabled px-1 text-[10px] uppercase tracking-wide text-text-disabled">Prueba</span>
                                    @endif
                                </p>
                                <p class="font-mono text-[11px] text-text-caption">{{ $pago->tiempoRelativo() }} · {{ $pago->created_at->setTimezone('America/Lima')->format('d/m H:i') }}</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1.5">
                                <span class="font-mono text-sm font-medium text-text-primary">{{ Dinero::soles($pago->monto) }}</span>
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

            <x-admin.card title="Productos" :action="$totalProductosActivos.' de '.$totalProductos.' activos'" class="md:min-h-0" scroll>
                <div class="flex flex-col gap-4">
                    @foreach ($productos as $fila)
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate text-sm font-medium text-text-primary">{{ $fila->producto->nombre }}</p>
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $fila->producto->activo ? 'bg-pagado-bg text-pagado-text' : 'bg-anulado-bg text-anulado-text' }}">
                                    {{ $fila->producto->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>
                            <p class="font-mono text-base font-semibold text-text-primary">{{ Dinero::soles($fila->producto->precio_centimos) }}</p>
                            <p class="text-xs text-text-caption">{{ $fila->ventas }} ventas · {{ $fila->intentosRecientes }} intentos recientes</p>
                        </div>
                    @endforeach

                    @if ($productos->sum('ventas') === 0)
                        <x-admin.empty-state
                            title="Aún sin ventas confirmadas"
                            description="Los ingresos por producto aparecen con el primer pago CAPTURED."
                        />
                    @endif

                    <a href="{{ route('admin.productos.index') }}" class="flex h-11 shrink-0 items-center justify-center rounded-nav bg-sidebar text-sm font-medium text-white hover:bg-sidebar-active">
                        Gestionar productos
                    </a>
                </div>
            </x-admin.card>
        </div>
    </div>
</x-layouts.admin-dashboard>
