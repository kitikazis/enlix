@props(['title', 'description' => null])

<div {{ $attributes->class(['flex flex-col items-center justify-center rounded-2xl border border-dashed border-border p-8 text-center']) }}>
    <p class="text-sm font-medium text-text-primary">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-xs text-text-caption">{{ $description }}</p>
    @endif
    {{ $slot ?? '' }}
</div>
