@component('mail::message')
# Nuevo {{ $reclamo->tipo->etiqueta() }}: N.° {{ $reclamo->numero_correlativo }}

**Consumidor:** {{ $reclamo->nombre_consumidor }} ({{ $reclamo->correo_consumidor }}, {{ $reclamo->telefono_consumidor }})
**{{ $reclamo->tipo_bien->etiqueta() }}:** {{ $reclamo->descripcion_bien }}
**Plazo de respuesta:** {{ $reclamo->fecha_limite_respuesta->translatedFormat('d \d\e F \d\e Y') }}

**Detalle:**
{{ $reclamo->detalle }}

**Pedido del consumidor:**
{{ $reclamo->pedido_consumidor }}

@component('mail::button', ['url' => route('admin.reclamos.show', $reclamo)])
Ver en el admin
@endcomponent
@endcomponent
