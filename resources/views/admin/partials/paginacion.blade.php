{{--
    Paginación del admin: $paginador->links('admin.partials.paginacion').
    La vista por defecto de Laravel (pagination::tailwind) usa clases que
    admin.css no compila (vendor/ no está en sus @source) y salía sin
    estilos. Móvil: Anterior / "Página X de Y" / Siguiente con 44px; desde
    sm se agregan los números de página.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación" class="flex items-center justify-between gap-3">
        <p class="text-sm text-text-secondary tabular-nums">
            <span class="sm:hidden">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            <span class="hidden sm:inline">
                Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}
            </span>
        </p>

        <div class="flex items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="btn btn-secondary btn-sm" aria-disabled="true">
                    <x-admin.icon name="flecha-izq" class="h-4 w-4" />
                    <span class="sr-only sm:not-sr-only">Anterior</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-secondary btn-sm">
                    <x-admin.icon name="flecha-izq" class="h-4 w-4" />
                    <span class="sr-only sm:not-sr-only">Anterior</span>
                </a>
            @endif

            <span class="hidden items-center gap-1 md:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-1.5 text-sm text-text-caption" aria-hidden="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $pagina => $url)
                            @if ($pagina == $paginator->currentPage())
                                <span aria-current="page" class="btn btn-primary btn-sm btn-icon tabular-nums">{{ $pagina }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-ghost btn-sm btn-icon tabular-nums" aria-label="Ir a la página {{ $pagina }}">{{ $pagina }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-secondary btn-sm">
                    <span class="sr-only sm:not-sr-only">Siguiente</span>
                    <x-admin.icon name="flecha-der" class="h-4 w-4" />
                </a>
            @else
                <span class="btn btn-secondary btn-sm" aria-disabled="true">
                    <span class="sr-only sm:not-sr-only">Siguiente</span>
                    <x-admin.icon name="flecha-der" class="h-4 w-4" />
                </span>
            @endif
        </div>
    </nav>
@endif
