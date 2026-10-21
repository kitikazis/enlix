@php
    use App\Support\Dinero;

    $filtrosActivos = collect([$filtros['estado'] ?? null, $filtros['desde'] ?? null, $filtros['hasta'] ?? null])->filter()->count();
    $urlLimpiar = route('admin.pagos', ['excluir_prueba' => $excluirPrueba ? '1' : '0']);
@endphp

<x-layouts.admin-dashboard :titulo="'Pagos - Enlix Admin'">
    <div class="flex flex-col gap-5 sm:gap-6">

        <x-admin.page-header
            title="Todos los pagos"
            :subtitle="trans_choice('1 pago en total|:count pagos en total', $pagos->total())"
        >
            <x-slot:actions>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary max-sm:hidden">
                    <x-admin.icon name="flecha-izq" class="h-4 w-4" />
                    Volver al dashboard
                </a>
            </x-slot:actions>
        </x-admin.page-header>

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
                    <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-end" aria-label="Filtrar pagos">
                        <input type="hidden" name="excluir_prueba" value="{{ $excluirPrueba ? '1' : '0' }}">
                        <div class="min-w-0 sm:col-span-2 lg:w-48">
                            <label for="filtro-estado" class="field-label">Estado</label>
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
                        <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                            <button type="submit" class="btn btn-primary flex-1 lg:flex-none">Filtrar</button>
                            <a href="{{ $urlLimpiar }}" class="btn btn-secondary flex-1 lg:flex-none">Limpiar</a>
                        </div>

                        <a
                            href="{{ request()->fullUrlWithQuery(['excluir_prueba' => $excluirPrueba ? '0' : '1']) }}"
                            role="switch"
                            aria-checked="{{ $excluirPrueba ? 'true' : 'false' }}"
                            class="btn justify-between sm:col-span-2 lg:ml-auto lg:justify-center {{ $excluirPrueba ? 'border-accent bg-accent/10 text-accent' : 'btn-secondary' }}"
                        >
                            <span>Excluir datos de prueba</span>
                            <span class="flex h-5 w-9 shrink-0 items-center rounded-full px-0.5 transition-colors {{ $excluirPrueba ? 'justify-end bg-accent' : 'justify-start bg-border' }}" aria-hidden="true">
                                <span class="h-4 w-4 rounded-full bg-white shadow"></span>
                            </span>
                        </a>
                    </form>
                </x-admin.filtros>
            </div>

            <x-admin.data-list
                caption="Pagos, del más reciente al más antiguo"
                scroll="y"
                :columns="['Fecha', 'Producto', ['label' => 'Monto', 'align' => 'right'], 'Estado', 'Pago', 'Order ID']"
            >
                @forelse ($pagos as $pago)
                    @php
                        $tarjeta = $pago->card_brand ? $pago->card_brand.' '.$pago->card_masked_pan : '—';
                        $fecha = $pago->created_at->setTimezone('America/Lima');
                    @endphp
                    <tr>
                        <td data-dl="title" class="whitespace-nowrap text-sm tabular-nums text-text-secondary max-lg:font-medium max-lg:text-text-primary">
                            <span><span class="lg:block lg:text-text-primary">{{ $fecha->format('d/m/Y') }}</span> <span class="lg:block lg:text-xs">{{ $fecha->format('H:i') }}</span></span>
                        </td>
                        <td data-label="Producto" class="lg:max-w-[12rem]">
                            <span class="flex min-w-0 items-center gap-2">
                                <span class="truncate font-medium text-text-primary">{{ $nombresProducto[$pago->producto] ?? $pago->producto }}</span>
                                @if ($pago->esPrueba())
                                    <x-admin.badge tone="prueba" :dot="false" class="max-lg:hidden">Prueba</x-admin.badge>
                                @endif
                            </span>
                            <span class="block truncate text-xs text-text-secondary max-lg:hidden" title="{{ $pago->email }}">{{ $pago->emailEnmascarado() }}</span>
                        </td>
                        <td data-label="Email" data-dl="mobile" class="text-text-secondary" title="{{ $pago->email }}">{{ $pago->emailEnmascarado() }}</td>
                        <td data-dl="aside" data-align="right" class="whitespace-nowrap font-mono font-semibold tabular-nums text-text-primary max-lg:text-base">{{ Dinero::soles($pago->monto) }}</td>
                        <td data-dl="status" class="whitespace-nowrap">
                            <x-admin.badge-estado :estado="$pago->estado" />
                            @if ($pago->esPrueba())
                                <x-admin.badge tone="prueba" :dot="false" class="lg:hidden">Prueba</x-admin.badge>
                            @endif
                        </td>
                        <td data-label="Método" class="whitespace-nowrap text-text-secondary">
                            <span>{{ $pago->metodo_pago?->etiqueta() ?? '—' }}</span><span class="max-lg:hidden">{{ $pago->card_brand ? ' · '.$pago->card_brand : '' }}</span>
                            @if ($pago->card_masked_pan)
                                <span class="block font-mono text-xs text-text-secondary max-lg:hidden">{{ $pago->card_masked_pan }}</span>
                            @endif
                        </td>
                        <td data-label="Tarjeta" data-dl="mobile" class="font-mono text-xs text-text-secondary">{{ $tarjeta }}</td>
                        <td data-label="Order ID" class="font-mono text-xs text-text-secondary">
                            <span class="block lg:max-w-[7.5rem] lg:truncate" title="{{ $pago->izipay_order_id }}">{{ $pago->izipay_order_id }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-admin.empty-state
                                icon="buscar"
                                title="No hay pagos con estos filtros"
                                description="Prueba con otro estado o un rango de fechas más amplio."
                                :action-url="$filtrosActivos ? $urlLimpiar : null"
                                action-label="Limpiar filtros"
                            />
                        </td>
                    </tr>
                @endforelse
            </x-admin.data-list>

            @if ($pagos->hasPages())
                <div class="border-t border-border p-4 sm:px-5">
                    {{ $pagos->links('admin.partials.paginacion') }}
                </div>
            @endif
        </x-admin.card>
    </div>
</x-layouts.admin-dashboard>
