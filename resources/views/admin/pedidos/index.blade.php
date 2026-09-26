<x-layouts.admin-dashboard :titulo="'Pedidos - Enlix Admin'">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">

        <div>
            <h1 class="text-2xl font-semibold text-text-primary md:text-[28px]">Pedidos</h1>
            <p class="text-sm text-text-caption">{{ $pagadosCount }} pagados de {{ $totalPedidos }} en total</p>
        </div>

        <x-admin.card title="Todos los pedidos">
            <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
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
                <div>
                    <label class="mb-1 block text-xs text-text-caption">Cliente, email o código</label>
                    <input type="text" name="cliente" value="{{ $filtros['cliente'] ?? '' }}" placeholder="ENX-... / nombre / email" class="h-9 w-56 rounded-nav border border-border bg-card px-2 text-sm">
                </div>
                <button type="submit" class="h-9 rounded-nav bg-accent px-3 text-sm font-medium text-white hover:bg-accent-hover">Filtrar</button>
                <a href="{{ route('admin.pedidos.index') }}" class="h-9 rounded-nav border border-border px-3 text-sm font-medium leading-9 text-text-secondary">Limpiar</a>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-border text-xs text-text-caption">
                            <th class="py-2 pr-3 font-medium">Código</th>
                            <th class="py-2 pr-3 font-medium">Fecha</th>
                            <th class="py-2 pr-3 font-medium">Cliente</th>
                            <th class="py-2 pr-3 text-right font-medium">Total</th>
                            <th class="py-2 pr-3 font-medium">Pago</th>
                            <th class="py-2 pr-3 font-medium">Método</th>
                            <th class="py-2 pr-3 font-medium">Envío</th>
                            <th class="py-2 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($pedidos as $pedido)
                            <tr>
                                <td class="whitespace-nowrap py-2.5 pr-3 font-mono text-xs text-text-secondary">{{ $pedido->codigo }}</td>
                                <td class="whitespace-nowrap py-2.5 pr-3 font-mono text-xs text-text-secondary">{{ $pedido->created_at->setTimezone('America/Lima')->format('d/m/Y H:i') }}</td>
                                <td class="max-w-[200px] truncate py-2.5 pr-3" title="{{ $pedido->email }}">
                                    {{ $pedido->nombre_cliente }}
                                </td>
                                <td class="whitespace-nowrap py-2.5 pr-3 text-right font-mono">S/ {{ number_format($pedido->total_centimos / 100, 2) }}</td>
                                <td class="whitespace-nowrap py-2.5 pr-3"><x-admin.badge-estado :estado="$pedido->estado_pago" /></td>
                                <td class="whitespace-nowrap py-2.5 pr-3 text-text-secondary">{{ $pedido->metodo_pago?->etiqueta() ?? '—' }}</td>
                                <td class="whitespace-nowrap py-2.5 pr-3">
                                    @if ($pedido->estado_envio)
                                        <x-admin.badge-estado :estado="$pedido->estado_envio" />
                                    @else
                                        <span class="text-text-caption">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap py-2.5 text-right">
                                    <a href="{{ route('admin.pedidos.show', $pedido) }}" class="text-xs font-medium text-accent hover:text-accent-hover">Ver →</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8">
                                    <x-admin.empty-state title="No hay pedidos con estos filtros." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $pedidos->links() }}
            </div>
        </x-admin.card>
    </div>
</x-layouts.admin-dashboard>
