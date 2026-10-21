@props(['estado'])

{{--
    El estado nunca se comunica solo con color: siempre va punto + texto
    (+ borde punteado en Expirado), para lectores de pantalla y usuarios con
    daltonismo. El title trae los códigos técnicos de Izipay: nunca se
    muestran sueltos en la pantalla.
--}}
<span
    {{ $attributes->class(['inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium leading-5', $estado->badgeClasses(), method_exists($estado, 'bordeClasses') ? $estado->bordeClasses() : '']) }}
    @if (method_exists($estado, 'codigosIzipay') && ! empty($estado->codigosIzipay())) title="{{ implode(' · ', $estado->codigosIzipay()) }}" @endif
>
    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $estado->dotClass() }}" aria-hidden="true"></span>
    {{ $estado->etiqueta() }}
</span>
