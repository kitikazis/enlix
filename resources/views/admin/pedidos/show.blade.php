@php
    $soles = fn ($centimos) => 'S/ '.number_format(((int) $centimos) / 100, 2);
    $fila = 'flex flex-col gap-0.5 py-2.5 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4';
    $valor = 'min-w-0 break-words text-text-primary sm:text-right';
@endphp

<x-layouts.admin-dashboard :titulo="'Pedido '.$pedido->codigo.' - Enlix Admin'">
    <div class="mx-auto flex w-full max-w-4xl flex-col gap-5 sm:gap-6">

        <x-admin.page-header
            :title="$pedido->codigo"
            title-class="font-mono"
            :back="route('admin.pedidos.index')"
            back-label="Todos los pedidos"
            :subtitle="$pedido->created_at->setTimezone('America/Lima')->format('d/m/Y H:i')"
        >
            <x-slot:actions>
                <x-admin.badge-estado :estado="$pedido->estado_pago" />
                @if ($pedido->estado_envio)
                    <x-admin.badge-estado :estado="$pedido->estado_envio" />
                @endif
                @if ($pedido->esPrueba())
                    <x-admin.badge tone="prueba" :dot="false">Prueba</x-admin.badge>
                @endif
            </x-slot:actions>
        </x-admin.page-header>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-admin.card title="Cliente">
                <dl class="-my-2.5 divide-y divide-border text-sm">
                    <div class="{{ $fila }}"><dt class="text-text-secondary">Nombre</dt><dd class="{{ $valor }}">{{ $pedido->nombre_cliente }}</dd></div>
                    <div class="{{ $fila }}"><dt class="text-text-secondary">Email</dt><dd class="{{ $valor }}">{{ $pedido->email }}</dd></div>
                    <div class="{{ $fila }}"><dt class="text-text-secondary">Teléfono</dt><dd class="{{ $valor }} tabular-nums">{{ $pedido->telefono }}</dd></div>
                    <div class="{{ $fila }}"><dt class="text-text-secondary">Documento</dt><dd class="{{ $valor }} tabular-nums">{{ $pedido->tipo_documento }} {{ $pedido->numero_documento }}</dd></div>
                    <div class="{{ $fila }}"><dt class="text-text-secondary">Comprobante</dt><dd class="{{ $valor }} capitalize">{{ $pedido->tipo_comprobante }}{{ $pedido->razon_social ? ' · '.$pedido->razon_social : '' }}</dd></div>
                </dl>
            </x-admin.card>

            <x-admin.card title="Entrega">
                <dl class="-my-2.5 divide-y divide-border text-sm">
                    <div class="{{ $fila }}"><dt class="text-text-secondary">Método</dt><dd class="{{ $valor }} capitalize">{{ $pedido->metodo_entrega }}</dd></div>
                    @if ($pedido->metodo_entrega === 'envio')
                        <div class="{{ $fila }}"><dt class="text-text-secondary">Dirección</dt><dd class="{{ $valor }}">{{ $pedido->direccion }}</dd></div>
                        <div class="{{ $fila }}"><dt class="text-text-secondary">Distrito</dt><dd class="{{ $valor }}">{{ $pedido->distrito }}</dd></div>
                        <div class="{{ $fila }}"><dt class="text-text-secondary">Ciudad</dt><dd class="{{ $valor }}">{{ $pedido->ciudad }}</dd></div>
                        @if ($pedido->referencia)
                            <div class="{{ $fila }}"><dt class="text-text-secondary">Referencia</dt><dd class="{{ $valor }}">{{ $pedido->referencia }}</dd></div>
                        @endif
                    @endif
                </dl>
            </x-admin.card>
        </div>

        <x-admin.card title="Items" :padded="false">
            <x-admin.data-list
                caption="Productos del pedido {{ $pedido->codigo }}"
                :columns="['SKU', 'Producto', ['label' => 'Cantidad', 'align' => 'right'], ['label' => 'Precio unit.', 'align' => 'right'], ['label' => 'Subtotal', 'align' => 'right']]"
            >
                @foreach ($pedido->items as $item)
                    <tr>
                        <td data-label="SKU" class="whitespace-nowrap font-mono text-xs text-text-secondary">{{ $item->sku ?? '—' }}</td>
                        <td data-dl="title" class="font-medium text-text-primary">{{ $item->nombre }}</td>
                        <td data-label="Cantidad" data-align="right" class="whitespace-nowrap font-mono tabular-nums">{{ $item->cantidad }}</td>
                        <td data-label="Precio unit." data-align="right" class="whitespace-nowrap font-mono tabular-nums">{{ $soles($item->precio_unitario_centimos) }}</td>
                        <td data-dl="aside" data-align="right" class="whitespace-nowrap font-mono font-semibold tabular-nums">{{ $soles($item->subtotal_centimos) }}</td>
                    </tr>
                @endforeach
            </x-admin.data-list>

            <dl class="flex flex-col gap-2 border-t border-border p-4 text-sm sm:px-5">
                <div class="flex justify-between gap-4"><dt class="text-text-secondary">Subtotal</dt><dd class="font-mono tabular-nums text-text-primary">{{ $soles($pedido->subtotal_centimos) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-text-secondary">Envío</dt><dd class="font-mono tabular-nums text-text-primary">{{ $soles($pedido->costo_envio_centimos) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-text-secondary">Descuento</dt><dd class="font-mono tabular-nums text-text-primary">- {{ $soles($pedido->descuento_centimos) }}</dd></div>
                <div class="mt-1 flex justify-between gap-4 border-t border-border pt-3 text-base font-semibold"><dt class="text-text-primary">Total</dt><dd class="font-mono tabular-nums text-text-primary">{{ $soles($pedido->total_centimos) }}</dd></div>
            </dl>
        </x-admin.card>

        <x-admin.card title="Pago (Izipay)">
            <dl class="-my-2.5 divide-y divide-border text-sm">
                <div class="{{ $fila }}"><dt class="text-text-secondary">Order ID</dt><dd class="{{ $valor }} font-mono text-xs">{{ $pedido->izipay_order_id ?? '—' }}</dd></div>
                <div class="{{ $fila }}"><dt class="text-text-secondary">Transaction UUID</dt><dd class="{{ $valor }} font-mono text-xs">{{ $pedido->transaction_uuid ?? '—' }}</dd></div>
                <div class="{{ $fila }}"><dt class="text-text-secondary">Método</dt><dd class="{{ $valor }}">{{ $pedido->metodo_pago?->etiqueta() ?? '—' }}</dd></div>
                <div class="{{ $fila }}"><dt class="text-text-secondary">Tarjeta</dt><dd class="{{ $valor }} font-mono text-xs">{{ $pedido->card_brand ? $pedido->card_brand.' '.$pedido->card_masked_pan : '—' }}</dd></div>
                <div class="{{ $fila }}"><dt class="text-text-secondary">Pagado el</dt><dd class="{{ $valor }} tabular-nums">{{ $pedido->pagado_en?->setTimezone('America/Lima')->format('d/m/Y H:i') ?? '—' }}</dd></div>
            </dl>
        </x-admin.card>
    </div>
</x-layouts.admin-dashboard>
