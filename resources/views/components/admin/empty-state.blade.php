@props(['title', 'description' => null, 'icon' => 'bandeja', 'actionUrl' => null, 'actionLabel' => null, 'tone' => 'neutral'])

@php
    $claseIcono = $tone === 'success' ? 'bg-pagado-bg text-pagado-text' : 'bg-page text-text-secondary';
@endphp
<div {{ $attributes->class(['flex flex-col items-center justify-center gap-3 rounded-card border border-dashed border-border px-6 py-8 text-center']) }}>
    <span class="flex h-12 w-12 items-center justify-center rounded-full {{ $claseIcono }}">
        <x-admin.icon :name="$icon" class="h-6 w-6" />
    </span>
    <div class="max-w-xs">
        <p class="text-sm font-semibold text-text-primary">{{ $title }}</p>
        @if ($description)
            <p class="mt-1 text-sm text-text-secondary">{{ $description }}</p>
        @endif
    </div>
    @if ($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="btn btn-secondary btn-sm">{{ $actionLabel }}</a>
    @endif
    {{ $slot ?? '' }}
</div>
