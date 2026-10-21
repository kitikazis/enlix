@props(['title' => null, 'padded' => true, 'scroll' => false, 'fill' => false])

<div {{ $attributes->class(['flex min-w-0 flex-col overflow-hidden rounded-card border border-border bg-card shadow-card']) }}>
    @if ($title)
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-border px-4 py-3 sm:px-5">
            <h2 class="truncate text-sm font-semibold text-text-primary">{{ $title }}</h2>
            @isset($action)
                <div class="shrink-0 text-xs text-text-secondary">{{ $action }}</div>
            @endisset
        </div>
    @endif

    {{--
        Sin x-data aqui a propósito: si el contenido de adentro necesita
        llamar a un método del x-data raíz de la página (ej. abrirEditar()
        en la tabla de productos), un x-data anidado nuevo rompe esa
        resolución de scope. actualizarScrollFade() es una función global
        plana (ver admin-dashboard.blade.php), no crea ningún scope.

        fill: el cuerpo ocupa todo el alto disponible de la tarjeta (para
        tablas con scroll propio, ver x-admin.data-list scroll="fill").
    --}}
    <div
        @if ($scroll) x-init="actualizarScrollFade($el)" @scroll="actualizarScrollFade($el)" @resize.window="actualizarScrollFade($el)" @endif
        @class([
            'min-w-0',
            'p-4 sm:p-5' => $padded,
            'dash-scroll xl:min-h-0 xl:flex-1 xl:overflow-y-auto' => $scroll,
            'flex min-h-0 flex-1 flex-col' => $fill,
        ])
    >
        {{ $slot }}
    </div>
</div>
