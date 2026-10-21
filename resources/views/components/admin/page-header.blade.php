@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => 'Volver', 'eyebrow' => null, 'titleClass' => '', 'breakpoint' => 'sm'])

{{--
    Header único de todas las pantallas del admin: (volver) + título +
    subtítulo a la izquierda, acciones a la derecha (abajo en móvil).
    subtitle acepta texto o un <x-slot:subtitle> (ej. con x-text de Alpine).
    breakpoint: desde qué ancho título y acciones van en una fila ('sm' o
    'lg' para headers con muchas acciones, como el del dashboard).
--}}
@php
    $claseFila = $breakpoint === 'lg'
        ? 'lg:flex-row lg:items-end lg:justify-between'
        : 'sm:flex-row sm:items-end sm:justify-between';
    $claseAcciones = $breakpoint === 'lg'
        ? 'lg:shrink-0 lg:justify-end'
        : 'sm:shrink-0 sm:justify-end';
@endphp
<header {{ $attributes->class(['flex shrink-0 flex-col gap-4', $claseFila]) }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="-ml-1 mb-1 inline-flex min-h-touch items-center gap-1.5 rounded-nav px-1 text-sm font-medium text-text-secondary hover:text-text-primary lg:min-h-8">
                <x-admin.icon name="flecha-izq" class="h-4 w-4" />
                {{ $backLabel }}
            </a>
        @endif
        @if ($eyebrow)
            <p class="text-sm text-text-secondary">{{ $eyebrow }}</p>
        @endif
        <h1 class="break-words text-2xl font-semibold tracking-tight text-text-primary sm:text-[1.75rem] sm:leading-9 {{ $titleClass }}">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-text-secondary">{{ $subtitle }}</p>
        @endif
        {{ $meta ?? '' }}
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2 {{ $claseAcciones }}">
            {{ $actions }}
        </div>
    @endisset
</header>
