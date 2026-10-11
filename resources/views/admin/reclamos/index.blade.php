<x-layouts.admin-dashboard :titulo="'Reclamos - Enlix Admin'" :badge-atencion="$pendientes ?: null">
    <div class="mx-auto flex w-full max-w-[1200px] flex-col gap-4">

        <div>
            <h1 class="text-xl font-semibold text-text-primary">Libro de Reclamaciones</h1>
            <p class="text-sm text-text-secondary">{{ $reclamos->total() }} registrados en total.</p>
        </div>

        @if (session('status'))
            <div class="rounded-nav border border-pagado-dot bg-pagado-bg px-4 py-3 text-sm text-pagado-text">
                {{ session('status') }}
            </div>
        @endif

        <x-admin.card :padded="false">
            @forelse ($reclamos as $reclamo)
                <a
                    href="{{ route('admin.reclamos.show', $reclamo) }}"
                    class="flex flex-col gap-2 border-b border-border px-5 py-4 last:border-b-0 hover:bg-page sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-text-primary">
                            N.° {{ $reclamo->numero_correlativo }} · {{ $reclamo->tipo->etiqueta() }}
                        </p>
                        <p class="truncate text-xs text-text-caption">
                            {{ $reclamo->nombre_consumidor }} · {{ $reclamo->descripcion_bien }}
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <span class="text-xs text-text-caption">
                            Vence {{ $reclamo->fecha_limite_respuesta->translatedFormat('d/m/Y') }}
                        </span>
                        <span class="rounded-full px-2.5 py-1 text-[11px] font-medium {{ $reclamo->estado->badgeClasses() }}">
                            {{ $reclamo->estado->etiqueta() }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="p-5">
                    <x-admin.empty-state title="Todavía no hay reclamos ni quejas registrados." />
                </div>
            @endforelse
        </x-admin.card>

        {{ $reclamos->links() }}

    </div>
</x-layouts.admin-dashboard>
