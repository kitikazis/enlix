<x-layouts.admin-dashboard :titulo="'Pedido '.$pedido->codigo.' - Enlix Admin'">
    <div class="mx-auto flex max-w-4xl flex-col gap-6">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('admin.pedidos.index') }}" class="text-xs font-medium text-accent hover:text-accent-hover">← Todos los pedidos</a>
                <h1 class="mt-1 font-mono text-2xl font-semibold text-text-primary">{{ $pedido->codigo }}</h1>
                <p class="text-sm text-text-caption">{{ $pedido->created_at->setTimezone('America/Lima')->format('d/m/Y H:i') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <x-admin.badge-estado :estado="$pedido->estado_pago" />
                @if ($pedido->estado_envio)
                    <x-admin.badge-estado :estado="$pedido->estado_envio" />
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <x-admin.card title="Cliente">
                <dl class="flex flex-col gap-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-text-caption">Nombre</dt><dd class="text-text-primary">{{ $pedido->nombre_cliente }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-text-caption">Email</dt><dd class="text-text-primary">{{ $pedido->email }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-text-caption">Teléfono</dt><dd class="text-text-primary">{{ $pedido->telefono }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-text-caption">Documento</dt><dd class="text-text-primary">{{ $pedido->tipo_documento }} {{ $pedido->numero_documento }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-text-caption">Comprobante</dt><dd class="text-text-primary capitalize">{{ $pedido->tipo_comprobante }}{{ $pedido->razon_social ? ' · '.$pedido->razon_social : '' }}</dd></div>
                </dl>
            </x-admin.card>

            <x-admin.card title="Entrega">
                <dl class="flex flex-col gap-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-text-caption">Método</dt><dd class="text-text-primary capitalize">{{ $pedido->metodo_entrega }}</dd></div>
                    @if ($pedido->metodo_entrega === 'envio')
                        <div class="flex justify-between gap-3"><dt class="text-text-caption">Dirección</dt><dd class="text-text-primary text-right">{{ $pedido->direccion }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-text-caption">Distrito</dt><dd class="text-text-primary">{{ $pedido->distrito }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-text-caption">Ciudad</dt><dd class="text-text-primary">{{ $pedido->ciudad }}</dd></div>
                        @if ($pedido->referencia)
                            <div class="flex justify-between gap-3"><dt class="text-text-caption">Referencia</dt><dd class="text-text-primary text-right">{{ $pedido->referencia }}</dd></div>
                        @endif
                    @endif
                </dl>
            </x-admin.card>
        </div>

        <x-admin.card title="Items">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-border text-xs text-text-caption">
                            <th class="py-2 pr-3 font-medium">SKU</th>
                            <th class="py-2 pr-3 font-medium">Producto</th>
                            <th class="py-2 pr-3 text-right font-medium">Cantidad</th>
                            <th class="py-2 pr-3 text-right font-medium">Precio unit.</th>
                            <th class="py-2 text-right font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($pedido->items as $item)
                            <tr>
                                <td class="whitespace-nowrap py-2.5 pr-3 font-mono text-xs text-text-caption">{{ $item->sku ?? '—' }}</td>
                                <td class="py-2.5 pr-3">{{ $item->nombre }}</td>
                                <td class="whitespace-nowrap py-2.5 pr-3 text-right font-mono">{{ $item->cantidad }}</td>
                                <td class="whitespace-nowrap py-2.5 pr-3 text-right font-mono">S/ {{ number_format($item->precio_unitario_centimos / 100, 2) }}</td>
                                <td class="whitespace-nowrap py-2.5 text-right font-mono">S/ {{ number_format($item->subtotal_centimos / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <dl class="mt-4 flex flex-col gap-1.5 border-t border-border pt-4 text-sm">
                <div class="flex justify-between"><dt class="text-text-caption">Subtotal</dt><dd class="font-mono text-text-primary">S/ {{ number_format($pedido->subtotal_centimos / 100, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-text-caption">Envío</dt><dd class="font-mono text-text-primary">S/ {{ number_format($pedido->costo_envio_centimos / 100, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-text-caption">Descuento</dt><dd class="font-mono text-text-primary">- S/ {{ number_format($pedido->descuento_centimos / 100, 2) }}</dd></div>
                <div class="flex justify-between text-base font-semibold"><dt class="text-text-primary">Total</dt><dd class="font-mono text-text-primary">S/ {{ number_format($pedido->total_centimos / 100, 2) }}</dd></div>
            </dl>
        </x-admin.card>

        <x-admin.card title="Pago (Izipay)">
            <dl class="flex flex-col gap-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-text-caption">Order ID</dt><dd class="font-mono text-xs text-text-primary">{{ $pedido->izipay_order_id ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-text-caption">Transaction UUID</dt><dd class="font-mono text-xs text-text-primary">{{ $pedido->transaction_uuid ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-text-caption">Método</dt><dd class="text-text-primary">{{ $pedido->metodo_pago?->etiqueta() ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-text-caption">Tarjeta</dt><dd class="font-mono text-xs text-text-primary">{{ $pedido->card_brand ? $pedido->card_brand.' '.$pedido->card_masked_pan : '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-text-caption">Pagado el</dt><dd class="text-text-primary">{{ $pedido->pagado_en?->setTimezone('America/Lima')->format('d/m/Y H:i') ?? '—' }}</dd></div>
            </dl>
        </x-admin.card>
    </div>
</x-layouts.admin-dashboard>
