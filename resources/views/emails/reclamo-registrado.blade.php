@component('mail::message')
# Copia de tu {{ $reclamo->tipo->etiqueta() }}

Hola {{ $reclamo->nombre_consumidor }}, este es un resumen de lo que registraste en nuestro Libro de Reclamaciones.

@component('mail::table')
| | |
| :--- | :--- |
| N.° de reclamo | {{ $reclamo->numero_correlativo }} |
| Tipo | {{ $reclamo->tipo->etiqueta() }} |
| Fecha | {{ $reclamo->created_at->setTimezone('America/Lima')->translatedFormat('d \d\e F \d\e Y, H:i') }} |
| {{ $reclamo->tipo_bien->etiqueta() }} reclamado | {{ $reclamo->descripcion_bien }} |
| Plazo de respuesta | {{ $reclamo->fecha_limite_respuesta->translatedFormat('d \d\e F \d\e Y') }} |
@endcomponent

**Detalle:**
{{ $reclamo->detalle }}

**Lo que solicitas:**
{{ $reclamo->pedido_consumidor }}

Tenemos un plazo de 15 días hábiles para responderte. Si tienes alguna consulta sobre tu reclamo, escríbenos a servicios@enlix.pe mencionando el número de arriba.

Gracias por tu confianza.

@component('mail::button', ['url' => url('/')])
Ir a Enlix
@endcomponent
@endcomponent
