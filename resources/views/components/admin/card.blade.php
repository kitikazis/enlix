@props(['title' => null])

<div {{ $attributes->class(['bg-card border border-border rounded-2xl']) }}>
    @if ($title)
        <div class="flex items-center justify-between gap-3 border-b border-border px-5 py-4">
            <h2 class="text-sm font-semibold text-text-primary">{{ $title }}</h2>
            @isset($action)
                <div>{{ $action }}</div>
            @endisset
        </div>
    @endif

    <div class="p-5">
        {{ $slot }}
    </div>
</div>
