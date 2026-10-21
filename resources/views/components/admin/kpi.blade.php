@props(['label', 'value', 'caption' => null, 'tone' => 'neutral', 'icon' => null, 'alerta' => null])

{{--
    tone: neutral | success | warning | danger. Con warning/danger la
    tarjeta entera cambia (borde, ícono y valor) y lleva una etiqueta de
    texto (alerta), para que la alerta no dependa solo del color.
--}}
@php
    $tonos = [
        'neutral' => ['card' => 'border-border', 'icono' => 'bg-page text-text-secondary', 'valor' => 'text-text-primary'],
        'success' => ['card' => 'border-border', 'icono' => 'bg-pagado-bg text-pagado-text', 'valor' => 'text-text-primary'],
        'warning' => ['card' => 'border-pendiente-dot/50', 'icono' => 'bg-pendiente-bg text-pendiente-text', 'valor' => 'text-pendiente-text'],
        'danger' => ['card' => 'border-rechazado-dot/60 ring-1 ring-rechazado-dot/20', 'icono' => 'bg-rechazado-bg text-rechazado-text', 'valor' => 'text-rechazado-text'],
    ];
    $t = $tonos[$tone] ?? $tonos['neutral'];
@endphp
<div {{ $attributes->class(['flex min-w-0 flex-col rounded-card border bg-card p-4 shadow-card sm:p-5 xl:p-[clamp(0.875rem,1.6vh,1.25rem)]', $t['card']]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 text-[0.8125rem] font-medium leading-5 text-text-secondary">{{ $label }}</div>
        @if ($icon)
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-nav {{ $t['icono'] }}">
                <x-admin.icon :name="$icon" class="h-5 w-5" />
            </span>
        @endif
    </div>
    <div class="mt-1 flex flex-wrap items-baseline gap-x-2 gap-y-1">
        <span class="truncate font-mono text-2xl font-semibold tabular-nums xl:text-[clamp(1.25rem,1.4vh+0.5vw,1.625rem)] {{ $t['valor'] }}">{{ $value }}</span>
        @if ($alerta)
            <x-admin.badge :tone="$tone === 'danger' ? 'danger' : 'warning'">{{ $alerta }}</x-admin.badge>
        @endif
    </div>
    @if ($caption)
        <div class="mt-1.5 text-xs leading-5 text-text-secondary">{{ $caption }}</div>
    @endif
</div>
