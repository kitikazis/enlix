@props(['title' => null, 'padded' => true])

<div {{ $attributes->class(['bg-card border border-border rounded-2xl overflow-hidden']) }}>
    @if ($title)
        <div class="flex items-center justify-between gap-3 border-b border-border px-5 py-4">
            <h2 class="text-sm font-semibold text-text-primary">{{ $title }}</h2>
            @isset($action)
                <div class="text-xs text-text-caption">{{ $action }}</div>
            @endisset
        </div>
    @endif

    <div @class(['p-5' => $padded])>
        {{ $slot }}
    </div>
</div>
