@php
    $filtrosActivos = collect([$filtros['estado'] ?? null, $filtros['desde'] ?? null, $filtros['hasta'] ?? null, $filtros['cliente'] ?? null])->filter()->count();
@endphp

<x-layouts.admin-dashboard :titulo="'Pedidos - Enlix Admin'">
    <div class="flex flex-col gap-5 sm:gap-6">

        <x-admin.page-header title="Pedidos" subtitle="{{ $pagadosCount }} pagados de {{ $totalPedidos }} en total" />

        <x-admin.card :padded="false">
            <div class="border-b border-border p-4 sm:px-5">
                @if ($errors->any())
                    <div class="mb-3 rounded-nav bg-rechazado-bg px-3 py-2 text-sm text-rechazado-text" role="alert">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <x-admin.filtros :activos="$filtrosActivos" :abierto="$errors->any()">
                    <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-end" aria-label="Filtrar pedidos">
                        <div class="min-w-0 sm:col-span-2 lg:w-64">
                            <label for="filtro-cliente" class="field-label">Cliente, email o código</label>
                            <input id="filtro-cliente" type="search" name="cliente" value="{{ $filtros['cliente'] ?? '' }}" placeholder="ENX-... / nombre / email" autocomplete="off" class="field-input">
                        </div>
                        <div class="min-w-0 sm:col-span-2 lg:w-44">
                            <label for="filtro-estado" class="field-label">Estado de pago</label>
                            <select id="filtro-estado" name="estado" class="field-input">
                                <option value="">Todos</option>
                                @foreach ($estados as $estado)
                                    <option value="{{ $estado->value }}" @selected(($filtros['estado'] ?? '') === $estado->value)>{{ $estado->etiqueta() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="min-w-0 lg:w-44">
                            <label for="filtro-desde" class="field-label">Desde</label>
                            <input id="filtro-desde" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" class="field-input" @error('desde') aria-invalid="true" @enderror>
                        </div>
                        <div class="min-w-0 lg:w-44">
                            <label for="filtro-hasta" class="field-label">Hasta</label>
                            <input id="filtro-hasta" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" class="field-input" @error('hasta') aria-invalid="true" @enderror>
                        </div>
                        <div class="flex gap-2 sm:col-span-2">
                            <button type="submit" class="btn btn-primary flex-1 lg:flex-none">Filtrar</button>
                            <a href="{{ route('admin.pedidos.index') }}" class="btn btn-secondary flex-1 lg:flex-none">Limpiar</a>
                        </div>
                    </form>
                </x-admin.filtros>
            </div>

            <x-admin.data-list
                caption="Pedidos, del más reciente al más antiguo"
                scroll="y"
                :columns="['Código', 'Fecha', 'Cliente', ['label' => 'Total', 'align' => 'right'], 'Pago', 'Método', 'Envío', ['label' => 'Acciones', 'sr' => true]]"
            >
                @forelse ($pedidos as $pedido)
                    <tr>
                        <td data-dl="title" class="whitespace-nowrap font-mono text-xs text-text-secondary max-lg:text-sm max-lg:font-semibold max-lg:text-text-primary">{{ $pedido->codigo }}</td>
                        <td data-label="Fecha" class="whitespace-nowrap tabular-nums text-text-secondary">
                            <span><span class="lg:block lg:text-text-primary">{{ $pedido->created_at->setTimezone('America/Lima')->format('d/m/Y') }}</span> <span class="lg:block lg:text-xs">{{ $pedido->created_at->setTimezone('America/Lima')->format('H:i') }}</span></span>
                        </td>
                        <td data-label="Cliente" class="lg:max-w-[11rem]">
                            <span class="block truncate text-text-primary" title="{{ $pedido->email }}">{{ $pedido->nombre_cliente }}</span>
                        </td>
                        <td data-dl="aside" data-align="right" class="whitespace-nowrap font-mono font-semibold tabular-nums text-text-primary max-lg:text-base">S/ {{ number_format($pedido->total_centimos / 100, 2) }}</td>
                        <td data-dl="status">
                            <span class="flex flex-wrap items-center gap-1">
                                <x-admin.badge-estado :estado="$pedido->estado_pago" />
                                @if ($pedido->esPrueba())
                                    <x-admin.badge tone="prueba" :dot="false">Prueba</x-admin.badge>
                                @endif
                            </span>
                        </td>
                        <td data-label="Método" class="whitespace-nowrap text-text-secondary">{{ $pedido->metodo_pago?->etiqueta() ?? '—' }}</td>
                        <td data-label="Envío" class="whitespace-nowrap">
                            @if ($pedido->estado_envio)
                                <x-admin.badge-estado :estado="$pedido->estado_envio" />
                            @else
                                <span class="text-text-secondary">—</span>
                            @endif
                        </td>
                        <td data-dl="actions" data-align="right" class="whitespace-nowrap">
                            <a href="{{ route('admin.pedidos.show', $pedido) }}" class="btn btn-secondary btn-sm text-accent max-lg:w-full lg:border-transparent lg:bg-transparent lg:hover:bg-page" aria-label="Ver pedido {{ $pedido->codigo }}">
                                Ver <span class="max-lg:hidden" aria-hidden="true">→</span><span class="lg:hidden">pedido</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-admin.empty-state
                                icon="pedidos"
                                title="No hay pedidos con estos filtros"
                                description="Prueba con otro estado, cliente o rango de fechas."
                                :action-url="$filtrosActivos ? route('admin.pedidos.index') : null"
                                action-label="Limpiar filtros"
                            />
                        </td>
                    </tr>
                @endforelse
            </x-admin.data-list>

            @if ($pedidos->hasPages())
                <div class="border-t border-border p-4 sm:px-5">
                    {{ $pedidos->links('admin.partials.paginacion') }}
                </div>
            @endif
        </x-admin.card>
    </div>
</x-layouts.admin-dashboard>
