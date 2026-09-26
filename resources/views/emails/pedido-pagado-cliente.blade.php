@component('mail::message')
# ¡Gracias por tu compra, {{ $pedido->nombre_cliente }}!

Tu pedido **{{ $pedido->codigo }}** fue confirmado y el pago se procesó correctamente.

@component('mail::table')
| Producto | Cantidad | Subtotal |
| :------- | :------: | -------: |
@foreach ($pedido->items as $item)
| {{ $item->nombre }} | {{ $item->cantidad }} | S/ {{ number_format($item->subtotal_centimos / 100, 2) }} |
@endforeach
@endcomponent

**Total: S/ {{ number_format($pedido->total_centimos / 100, 2) }}**

@if ($pedido->metodo_entrega === 'envio')
Enviaremos tu pedido a: {{ $pedido->direccion }}, {{ $pedido->distrito }}.
@else
Puedes recoger tu pedido en tienda.
@endif

Gracias por confiar en Enlix.

@component('mail::button', ['url' => url('/')])
Ir a Enlix
@endcomponent
@endcomponent
