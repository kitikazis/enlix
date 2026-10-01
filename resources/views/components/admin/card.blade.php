@props(['title' => null, 'padded' => true, 'scroll' => false])

<div {{ $attributes->class(['flex flex-col bg-card border border-border rounded-2xl overflow-hidden']) }}>
    @if ($title)
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-border px-[clamp(0.875rem,1.5vh,1.25rem)] py-[clamp(0.625rem,1.25vh,1rem)]">
            <h2 class="truncate text-sm font-semibold text-text-primary">{{ $title }}</h2>
            @isset($action)
                <div class="shrink-0 text-xs text-text-caption">{{ $action }}</div>
            @endisset
        </div>
    @endif

    <div
        @if ($scroll) x-data="scrollFade()" x-init="actualizarFade()" @scroll="actualizarFade()" @resize.window="actualizarFade()" :class="{ 'dash-scroll-fade': desbordado }" @endif
        @class(['p-[clamp(0.875rem,1.5vh,1.25rem)]' => $padded, 'dash-scroll md:min-h-0 md:flex-1 md:overflow-y-auto' => $scroll])
    >
        {{ $slot }}
    </div>
</div>
