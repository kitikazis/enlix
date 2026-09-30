@props(['label', 'value', 'caption' => null, 'valueClass' => 'text-text-primary'])

<div {{ $attributes->class(['flex flex-col justify-center bg-card border border-border rounded-2xl p-[clamp(0.75rem,1.6vh,1.25rem)]']) }}>
    <div class="truncate text-[clamp(0.6875rem,0.5vw+0.5rem,0.8125rem)] text-text-caption">{{ $label }}</div>
    <div class="mt-1 truncate font-mono text-[clamp(1.0625rem,1.4vh+0.4vw,1.5rem)] font-semibold {{ $valueClass }}">{{ $value }}</div>
    @if ($caption)
        <div class="mt-1 truncate text-[clamp(0.625rem,0.4vw+0.5rem,0.75rem)] text-text-caption">{{ $caption }}</div>
    @endif
</div>
