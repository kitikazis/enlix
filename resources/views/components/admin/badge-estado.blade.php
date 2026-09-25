@props(['estado'])

{{--
    El estado nunca se comunica solo con color: siempre va punto + texto,
    para lectores de pantalla y usuarios con daltonismo.
--}}
<span {{ $attributes->class(['inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium', $estado->badgeClasses()]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $estado->dotClass() }}" aria-hidden="true"></span>
    {{ $estado->etiqueta() }}
</span>
