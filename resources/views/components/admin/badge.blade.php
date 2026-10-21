@props(['tone' => 'neutral', 'dot' => true])

{{--
    Badge genérico (Activo/Inactivo, estado de reclamo, Destacado, PRUEBA).
    Igual que x-admin.badge-estado: nunca solo color, siempre texto (+ punto
    o borde). tone="prueba" es la etiqueta de datos de prueba: ámbar sólido
    con borde, en mayúsculas, para que se vea de lejos.
--}}
@php
    $tonos = [
        'success' => ['bg-pagado-bg text-pagado-text', 'bg-pagado-dot'],
        'warning' => ['bg-pendiente-bg text-pendiente-text', 'bg-pendiente-dot'],
        'info' => ['bg-verificacion-bg text-verificacion-text', 'bg-verificacion-dot'],
        'danger' => ['bg-rechazado-bg text-rechazado-text', 'bg-rechazado-dot'],
        'neutral' => ['bg-anulado-bg text-anulado-text', 'bg-anulado-dot'],
        'muted' => ['border border-dashed border-expirado-dot bg-expirado-bg text-expirado-text', 'bg-expirado-dot'],
        'prueba' => ['border border-pendiente-dot bg-pendiente-bg font-semibold uppercase tracking-wide text-pendiente-text', 'bg-pendiente-dot'],
    ];
    [$clases, $claseDot] = $tonos[$tone] ?? $tonos['neutral'];
@endphp
<span {{ $attributes->class(['inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium leading-5', $clases]) }}>
    @if ($dot)
        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $claseDot }}" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
