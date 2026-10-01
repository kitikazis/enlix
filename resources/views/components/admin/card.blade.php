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

    {{--
        Sin x-data aqui a propósito: si el contenido de adentro necesita
        llamar a un método del x-data raíz de la página (ej. abrirEditar()
        en la tabla de productos), un x-data anidado nuevo rompe esa
        resolución de scope. actualizarScrollFade() es una función global
        plana (ver admin-dashboard.blade.php), no crea ningún scope.
    --}}
    <div
        @if ($scroll) x-init="actualizarScrollFade($el)" @scroll="actualizarScrollFade($el)" @resize.window="actualizarScrollFade($el)" @endif
        @class(['p-[clamp(0.875rem,1.5vh,1.25rem)]' => $padded, 'dash-scroll md:min-h-0 md:flex-1 md:overflow-y-auto' => $scroll])
    >
        {{ $slot }}
    </div>
</div>
