@props(['estado'])

{{--
    El estado nunca se comunica solo con color: siempre va punto + texto
    (+ borde punteado en Expirado), para lectores de pantalla y usuarios con
    daltonismo. El title trae los códigos técnicos de Izipay: nunca se
    muestran sueltos en la pantalla.
--}}
<span
    {{ $attributes->class(['inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium', $estado->badgeClasses(), $estado->bordeClasses()]) }}
    @if (! empty($estado->codigosIzipay())) title="{{ implode(' · ', $estado->codigosIzipay()) }}" @endif
>
    <span class="h-1.5 w-1.5 rounded-full {{ $estado->dotClass() }}" aria-hidden="true"></span>
    {{ $estado->etiqueta() }}
</span>
