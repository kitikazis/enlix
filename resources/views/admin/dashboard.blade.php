@php
    use App\Enums\EstadoPago;
    use App\Support\Dinero;

    $rangos = ['hoy' => 'Hoy', '7d' => '7 días', '30d' => '30 días', 'mes' => 'Este mes'];

    // Mismos umbrales que antes; ahora además cambian el tono de la tarjeta
    // entera y agregan una etiqueta de texto (la alerta no depende solo del color).
    [$conversionTono, $conversionAlerta] = match (true) {
        $tasaConversion == 0.0 => ['danger', 'Sin ventas'],
        $tasaConversion < 20 => ['warning', 'Baja'],
        default => ['neutral', null],
    };
    [$rechazoTono, $rechazoAlerta] = match (true) {
        $tasaRechazo > 15 => ['danger', 'Alta'],
        $tasaRechazo > 5 => ['warning', 'Revisar'],
        default => ['neutral', null],
    };

    $resumenBarra = collect($estados)
        ->map(fn ($e) => $e->etiqueta().': '.($porEstado[$e->value] ?? 0))
        ->implode(', ');
    $hayAlgoQueRevisar = $enVerificacionEnRango > 0 || $pendientesEnRango > 0 || (! $excluirPrueba && $ultimosPruebaCount > 0);
@endphp

<x-layouts.admin-dashboard :titulo="'Dashboard - Enlix Admin'" :badge-atencion="$pendientesEnRango + $enVerificacionEnRango ?: null" sin-scroll="xl">
    <div class="flex flex-col gap-4 sm:gap-5 xl:grid xl:min-h-0 xl:flex-1 xl:grid-rows-[auto_auto_minmax(0,1fr)_minmax(0,1fr)] xl:gap-4">

        <x-admin.page-header title="Dashboard" :eyebrow="$fechaLarga" breakpoint="lg">
            <x-slot:actions>
                <div class="grid w-full grid-cols-4 gap-1 rounded-nav border border-border bg-card p-1 shadow-card sm:inline-grid sm:w-auto" role="group" aria-label="Rango de fechas">
                    @foreach ($rangos as $valor => $etiqueta)
                        <a
                            href="{{ request()->fullUrlWithQuery(['rango' => $valor]) }}"
                            @if ($rango === $valor) aria-current="true" @endif
                            class="flex min-h-touch items-center justify-center rounded-[0.5rem] px-1 text-center sm:px-3 text-sm font-medium whitespace-nowrap transition-colors lg:min-h-9 {{ $rango === $valor ? 'bg-accent text-on-accent shadow-card' : 'text-text-secondary hover:bg-page hover:text-text-primary' }}"
                        >{{ $etiqueta }}</a>
                    @endforeach
                </div>

                <div class="flex w-full items-center gap-2 sm:w-auto">
                    <button
                        type="button"
                        @click="modoModalAbierto = true"
                        class="btn flex-1 sm:flex-none {{ $excluirPrueba ? 'btn-secondary' : 'border-accent bg-accent/10 text-accent' }}"
                    >
                        <span class="h-2 w-2 shrink-0 rounded-full {{ $excluirPrueba ? 'bg-pagado-dot' : 'bg-pendiente-dot' }}" aria-hidden="true"></span>
                        Modo: {{ $excluirPrueba ? 'Real' : 'Prueba' }}
                    </button>

                    <a
                        href="{{ route('admin.dashboard.exportar', ['rango' => $rango, 'excluir_prueba' => $excluirPrueba ? '1' : '0']) }}"
                        download
                        class="btn btn-secondary w-touch shrink-0 px-0 sm:w-auto sm:px-4"
                        aria-label="Exportar CSV de {{ mb_strtolower($rangos[$rango]) }}"
                        title="Exportar CSV"
                    >
                        <x-admin.icon name="descargar" class="h-4 w-4" />
                        <span class="hidden sm:inline">Exportar CSV</span>
                    </a>
                </div>
            </x-slot:actions>
        </x-admin.page-header>

        {{-- KPIs --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-4">
            <x-admin.kpi
                label="Ingresos · {{ $rangos[$rango] }}"
                value="{{ Dinero::soles($ingresosRango) }}"
                icon="ingresos"
                tone="success"
                caption="Total histórico {{ Dinero::soles($ingresosTotal) }} · Hoy {{ Dinero::soles($ingresosHoy) }}"
            />
            <x-admin.kpi
                label="Intentos de pago · {{ $rangos[$rango] }}"
                value="{{ $intentos }}"
                icon="intentos"
                caption="Ticket promedio intentado {{ Dinero::soles($ticketPromedio) }}"
            />
            <x-admin.kpi
                label="Tasa de conversión · {{ $rangos[$rango] }}"
                value="{{ $tasaConversion }}%"
                icon="conversion"
                :tone="$conversionTono"
                :alerta="$conversionAlerta"
                caption="{{ $porEstado[EstadoPago::Pagado->value] ?? 0 }} de {{ $intentos }} intentos terminaron pagados"
            />
            <x-admin.kpi
                label="Tasa de rechazo · {{ $rangos[$rango] }}"
                value="{{ $tasaRechazo }}%"
                icon="rechazo"
                :tone="$rechazoTono"
                :alerta="$rechazoAlerta"
                caption="{{ $rechazadosEnRango }} rechazados por emisor o pasarela"
            />
        </div>

        {{-- Pagos por estado + Requiere tu atención --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 xl:min-h-0">
            <x-admin.card title="Pagos por estado" class="lg:col-span-2 xl:min-h-0" scroll>
                <x-slot:action>{{ $intentos }} intentos · elige uno para filtrar</x-slot:action>

                <div
                    class="mb-4 flex h-3 gap-[2px] overflow-hidden rounded-full bg-page"
                    role="img"
                    aria-label="Distribución de {{ $intentos }} intentos. {{ $resumenBarra }}"
                >
                    @foreach ($estados as $estado)
                        @php
                            $cantidadBar = $porEstado[$estado->value] ?? 0;
                            $pctBar = $intentos > 0 ? ($cantidadBar / $intentos * 100) : 0;
                        @endphp
                        @if ($pctBar > 0)
                            <div class="{{ $estado->dotClass() }} transition-[width] duration-500" style="width: {{ $pctBar }}%" title="{{ $estado->etiqueta() }}: {{ $cantidadBar }} ({{ round($pctBar, 1) }}%)"></div>
                        @endif
                    @endforeach
                </div>

                {{-- Las tarjetas son también la leyenda de la barra (punto + nombre + cantidad + %). --}}
                <div class="grid grid-cols-2 gap-2 sm:gap-3 lg:grid-cols-3">
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
                            @if ($seleccionado) aria-current="true" @endif
                            aria-label="{{ $estado->etiqueta() }}: {{ $cantidad }} ({{ $pct }}%). {{ $seleccionado ? 'Filtro activo, toca para quitarlo' : 'Filtrar por este estado' }}"
                            title="{{ $titleTile }}"
                            class="flex min-h-touch min-w-0 flex-col gap-1 rounded-nav border p-3 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-card {{ $seleccionado ? $estado->seleccionadoClasses() : 'border-border hover:border-text-disabled hover:shadow-card' }}"
                        >
                            <span class="flex min-w-0 items-center gap-1.5 text-sm font-medium text-text-primary">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $estado->dotClass() }}" aria-hidden="true"></span>
                                <span class="truncate">{{ $estado->etiqueta() }}</span>
                            </span>
                            <span class="flex items-baseline gap-1.5">
                                <span class="font-mono text-lg font-semibold tabular-nums text-text-primary">{{ $cantidad }}</span>
                                <span class="font-mono text-xs tabular-nums text-text-secondary">{{ $pct }}%</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </x-admin.card>

            <x-admin.card title="Requiere tu atención" class="xl:min-h-0" scroll>
                <div class="flex h-full flex-col gap-3">
                    @if ($enVerificacionEnRango > 0 && $masAntiguoEnVerificacion)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-nav bg-verificacion-bg p-3">
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-verificacion-text">
                                    {{ trans_choice('1 pago en verificación|:count pagos en verificación', $enVerificacionEnRango) }}
                                </span>
                                <span class="block truncate text-xs text-verificacion-text">
                                    {{ $nombresProducto[$masAntiguoEnVerificacion->producto] ?? $masAntiguoEnVerificacion->producto }}
                                    · {{ Dinero::soles($masAntiguoEnVerificacion->monto) }}
                                    · {{ $masAntiguoEnVerificacion->tiempoRelativo() }}
                                </span>
                            </span>
                            <a
                                href="{{ route('admin.pagos', ['estado' => 'en_verificacion', 'excluir_prueba' => $excluirPrueba ? '1' : '0']) }}"
                                class="btn btn-sm shrink-0 border-verificacion-dot bg-card text-verificacion-text hover:bg-page"
                            >Revisar</a>
                        </div>
                    @endif

                    @if ($pendientesEnRango > 0)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-nav bg-pendiente-bg p-3">
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-pendiente-text">{{ trans_choice('1 pago sin completar|:count pagos sin completar', $pendientesEnRango) }}</span>
                                <span class="block text-xs text-pendiente-text">Iniciados y abandonados en checkout</span>
                            </span>
                            <a
                                href="{{ route('admin.pagos', ['estado' => 'pendiente', 'excluir_prueba' => $excluirPrueba ? '1' : '0']) }}"
                                class="btn btn-sm shrink-0 border-pendiente-dot bg-card text-pendiente-text hover:bg-page"
                            >Ver</a>
                        </div>
                    @endif

                    @if (! $excluirPrueba && $ultimosPruebaCount > 0)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-nav border border-border bg-page p-3">
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-text-primary">Datos de prueba en producción</span>
                                <span class="block text-xs text-text-secondary">
                                    {{ trans_choice('1 de los últimos 10 pagos parece de prueba|:count de los últimos 10 pagos parecen de prueba', $ultimosPruebaCount) }}
                                </span>
                            </span>
                            <a
                                href="{{ request()->fullUrlWithQuery(['excluir_prueba' => '1']) }}"
                                class="btn btn-secondary btn-sm shrink-0"
                            >Excluir</a>
                        </div>
                    @endif

                    @unless ($hayAlgoQueRevisar)
                        <x-admin.empty-state
                            class="flex-1"
                            icon="check"
                            tone="success"
                            title="Todo al día"
                            description="No hay pagos pendientes ni en verificación en este rango."
                            :action-url="route('admin.pagos', ['excluir_prueba' => $excluirPrueba ? '1' : '0'])"
                            action-label="Ver todos los pagos"
                        />
                    @endunless
                </div>
            </x-admin.card>
        </div>

        {{-- Últimos pagos + Productos --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 xl:min-h-0">
            <x-admin.card title="Últimos pagos" class="lg:col-span-2 xl:min-h-0" :padded="false">
                <x-slot:action>
                    <a href="{{ route('admin.pagos', ['excluir_prueba' => $excluirPrueba ? '1' : '0']) }}" class="-my-2 inline-flex min-h-touch items-center gap-1 text-sm font-medium text-accent hover:text-accent-hover lg:min-h-8">
                        Ver todos <x-admin.icon name="flecha-der" class="h-4 w-4" />
                    </a>
                </x-slot:action>

                <ul
                    x-init="actualizarScrollFade($el)" @scroll="actualizarScrollFade($el)" @resize.window="actualizarScrollFade($el)"
                    class="dash-scroll divide-y divide-border xl:min-h-0 xl:flex-1 xl:overflow-y-auto"
                    aria-label="Últimos pagos"
                >
                    @forelse ($ultimosPagos as $pago)
                        <li class="flex items-start justify-between gap-3 px-4 py-3 transition-colors hover:bg-row-hover sm:px-5">
                            <div class="min-w-0">
                                <p class="flex min-w-0 items-center gap-2">
                                    <span class="truncate text-sm font-medium text-text-primary">{{ $nombresProducto[$pago->producto] ?? $pago->producto }}</span>
                                    @if ($pago->esPrueba())
                                        <x-admin.badge tone="prueba" :dot="false">Prueba</x-admin.badge>
                                    @endif
                                </p>
                                <p class="truncate text-xs text-text-secondary" title="{{ $pago->email }}">{{ $pago->emailEnmascarado() }}</p>
                                <p class="text-xs tabular-nums text-text-secondary">{{ $pago->tiempoRelativo() }} · {{ $pago->created_at->setTimezone('America/Lima')->format('d/m H:i') }}</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1.5">
                                <span class="font-mono text-sm font-semibold tabular-nums text-text-primary">{{ Dinero::soles($pago->monto) }}</span>
                                <x-admin.badge-estado :estado="$pago->estado" />
                            </div>
                        </li>
                    @empty
                        <li class="p-4 sm:p-5">
                            <x-admin.empty-state
                                icon="pagos"
                                title="No hay pagos en este rango"
                                description="Prueba con un rango más amplio."
                                :action-url="request()->fullUrlWithQuery(['rango' => '30d'])"
                                action-label="Ver últimos 30 días"
                            />
                        </li>
                    @endforelse
                </ul>
            </x-admin.card>

            <x-admin.card title="Productos" :action="$totalProductosActivos.' de '.$totalProductos.' activos'" class="xl:min-h-0" scroll>
                <div class="flex h-full flex-col gap-4">
                    <ul class="flex flex-col gap-4">
                        @foreach ($productos as $fila)
                            <li class="min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="flex min-w-0 items-center gap-2">
                                        <span class="truncate text-sm font-medium text-text-primary">{{ $fila->producto->nombre }}</span>
                                        @if ($fila->producto->esPrueba())
                                            <x-admin.badge tone="prueba" :dot="false">Prueba</x-admin.badge>
                                        @endif
                                    </p>
                                    <x-admin.badge :tone="$fila->producto->activo ? 'success' : 'neutral'">{{ $fila->producto->activo ? 'Activo' : 'Inactivo' }}</x-admin.badge>
                                </div>
                                <p class="font-mono text-base font-semibold tabular-nums text-text-primary">{{ Dinero::soles($fila->producto->precio_centimos) }}</p>
                                <p class="text-xs text-text-secondary">{{ $fila->ventas }} ventas · {{ $fila->intentosRecientes }} intentos recientes</p>
                            </li>
                        @endforeach
                    </ul>

                    @if ($productos->sum('ventas') === 0)
                        <x-admin.empty-state
                            class="flex-1"
                            icon="ingresos"
                            title="Aún sin ventas confirmadas"
                            description="Los ingresos por producto aparecen con el primer pago CAPTURED."
                        />
                    @endif

                    <a href="{{ route('admin.productos.index') }}" class="btn btn-secondary mt-auto w-full shrink-0">
                        Gestionar productos
                    </a>
                </div>
            </x-admin.card>
        </div>
    </div>
</x-layouts.admin-dashboard>
