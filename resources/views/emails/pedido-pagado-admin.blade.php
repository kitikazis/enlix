@component('mail::message')
# Nuevo pedido pagado: {{ $pedido->codigo }}

**Cliente:** {{ $pedido->nombre_cliente }} ({{ $pedido->email }}, {{ $pedido->telefono }})
**Total:** S/ {{ number_format($pedido->total_centimos / 100, 2) }}
**Método de entrega:** {{ ucfirst($pedido->metodo_entrega) }}
@if ($pedido->metodo_entrega === 'envio')
**Dirección:** {{ $pedido->direccion }}, {{ $pedido->distrito }}
@endif

@component('mail::table')
| Producto | SKU | Cantidad |
| :------- | :-- | :------: |
@foreach ($pedido->items as $item)
| {{ $item->nombre }} | {{ $item->sku ?? '—' }} | {{ $item->cantidad }} |
@endforeach
@endcomponent

@component('mail::button', ['url' => route('admin.pedidos.show', $pedido)])
Ver pedido en el admin
@endcomponent
@endcomponent
