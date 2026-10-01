@php
    use App\Support\Dinero;
@endphp

<x-layouts.admin-dashboard :titulo="'Pagos - Enlix Admin'">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">

        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-text-primary md:text-[28px]">Todos los pagos</h1>
                <p class="text-sm text-text-caption">{{ trans_choice('1 pago en total|:count pagos en total', $pagos->total()) }}</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex h-10 items-center justify-center rounded-nav border border-border bg-card px-4 text-sm font-medium text-text-primary hover:bg-page">
                ← Volver al dashboard
            </a>
        </div>

        <x-admin.card :padded="false">
            <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-border p-4">
                <input type="hidden" name="excluir_prueba" value="{{ $excluirPrueba ? '1' : '0' }}">
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
                <a href="{{ route('admin.pagos', ['excluir_prueba' => $excluirPrueba ? '1' : '0']) }}" class="h-9 rounded-nav border border-border px-3 text-sm font-medium leading-9 text-text-secondary">Limpiar</a>

                <a
                    href="{{ request()->fullUrlWithQuery(['excluir_prueba' => $excluirPrueba ? '0' : '1']) }}"
                    class="ml-auto inline-flex h-9 items-center gap-2 rounded-nav border px-3 text-sm font-medium {{ $excluirPrueba ? 'border-accent bg-accent/10 text-accent' : 'border-border text-text-secondary hover:bg-page' }}"
                    aria-pressed="{{ $excluirPrueba ? 'true' : 'false' }}"
                >
                    <span class="flex h-4 w-7 items-center rounded-full transition-colors {{ $excluirPrueba ? 'bg-accent justify-end' : 'bg-border justify-start' }} px-0.5">
                        <span class="h-3 w-3 rounded-full bg-white shadow"></span>
                    </span>
                    Excluir datos de prueba
                </a>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-border text-xs text-text-caption">
                            <th class="py-2 pl-5 pr-3 font-medium">Fecha</th>
                            <th class="py-2 pr-3 font-medium">Producto</th>
                            <th class="py-2 pr-3 font-medium">Email</th>
                            <th class="py-2 pr-3 text-right font-medium">Monto</th>
                            <th class="py-2 pr-3 font-medium">Estado</th>
                            <th class="py-2 pr-3 font-medium">Método</th>
                            <th class="py-2 pr-3 font-medium">Tarjeta</th>
                            <th class="py-2 pr-5 font-medium">Order ID</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($pagos as $pago)
                            <tr>
                                <td class="whitespace-nowrap py-2.5 pl-5 pr-3 font-mono text-xs text-text-secondary">{{ $pago->created_at->setTimezone('America/Lima')->format('d/m/Y H:i') }}</td>
                                <td class="whitespace-nowrap py-2.5 pr-3">{{ $nombresProducto[$pago->producto] ?? $pago->producto }}</td>
                                <td class="max-w-[180px] truncate py-2.5 pr-3 text-text-secondary" title="{{ $pago->email }}">
                                    {{ $pago->emailEnmascarado() }}
                                    @if ($pago->esPrueba())
                                        <span class="ml-1 rounded border border-dashed border-text-disabled px-1 text-[10px] uppercase tracking-wide text-text-disabled">Prueba</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap py-2.5 pr-3 text-right font-mono">{{ Dinero::soles($pago->monto) }}</td>
                                <td class="whitespace-nowrap py-2.5 pr-3"><x-admin.badge-estado :estado="$pago->estado" /></td>
                                <td class="whitespace-nowrap py-2.5 pr-3 text-text-secondary">{{ $pago->metodo_pago?->etiqueta() ?? '—' }}</td>
                                <td class="whitespace-nowrap py-2.5 pr-3 font-mono text-xs text-text-secondary">{{ $pago->card_brand ? $pago->card_brand.' '.$pago->card_masked_pan : '—' }}</td>
                                <td class="max-w-[140px] truncate py-2.5 pr-5 font-mono text-xs text-text-caption" title="{{ $pago->izipay_order_id }}">{{ $pago->izipay_order_id }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8">
                                    <x-admin.empty-state title="No hay pagos con estos filtros." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4">
                {{ $pagos->links() }}
            </div>
        </x-admin.card>
    </div>
</x-layouts.admin-dashboard>
