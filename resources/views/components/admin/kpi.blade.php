@props(['label', 'value', 'caption' => null, 'valueClass' => 'text-text-primary'])

<div {{ $attributes->class(['bg-card border border-border rounded-2xl p-5']) }}>
    <div class="text-sm text-text-caption">{{ $label }}</div>
    <div class="mt-2 font-mono text-2xl font-semibold {{ $valueClass }}">{{ $value }}</div>
    @if ($caption)
        <div class="mt-1 text-xs text-text-caption">{{ $caption }}</div>
    @endif
</div>
