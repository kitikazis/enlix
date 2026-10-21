@props(['activos' => 0, 'abierto' => false])

{{--
    Panel de filtros: siempre visible desde lg; en móvil/tablet se pliega
    detrás de un botón "Filtros" con el contador de filtros activos.
    Arranca abierto si hay errores de validación (el admin tiene que ver
    qué campo falló). Solo para páginas SIN x-data propio (Pagos, Pedidos):
    este x-data anidado taparía los métodos de un componente de página.
--}}
<div x-data="{ filtrosAbiertos: {{ $abierto ? 'true' : 'false' }} }" {{ $attributes->class(['flex flex-col gap-3']) }}>
    <button
        type="button"
        class="btn btn-secondary w-full justify-between lg:hidden"
        @click="filtrosAbiertos = !filtrosAbiertos"
        :aria-expanded="filtrosAbiertos ? 'true' : 'false'"
        aria-expanded="{{ $abierto ? 'true' : 'false' }}"
        aria-controls="panel-filtros"
    >
        <span class="inline-flex items-center gap-2">
            <x-admin.icon name="filtro" class="h-4 w-4" />
            Filtros
            @if ($activos > 0)
                <span class="count-badge bg-accent text-on-accent">{{ $activos }}</span>
                <span class="sr-only">{{ $activos === 1 ? 'filtro activo' : 'filtros activos' }}</span>
            @endif
        </span>
        <x-admin.icon name="chevron" class="h-4 w-4 transition-transform" x-bind:class="filtrosAbiertos && 'rotate-180'" />
    </button>

    <div id="panel-filtros" class="{{ $abierto ? '' : 'max-lg:hidden' }}" :class="filtrosAbiertos ? 'max-lg:block!' : ''">
        {{ $slot }}
    </div>
</div>
