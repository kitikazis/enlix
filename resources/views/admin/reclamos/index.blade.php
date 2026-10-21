@php
    use App\Enums\EstadoReclamo;
@endphp

<x-layouts.admin-dashboard :titulo="'Reclamos - Enlix Admin'" :badge-atencion="$pendientes ?: null">
    <div class="flex flex-col gap-5 sm:gap-6">

        <x-admin.page-header title="Libro de Reclamaciones" subtitle="{{ $reclamos->total() }} registrados en total." />

        @if (session('status'))
            <div class="rounded-nav border border-pagado-dot bg-pagado-bg px-4 py-3 text-sm text-pagado-text" role="status">
                {{ session('status') }}
            </div>
        @endif

        <x-admin.card :padded="false">
            <ul class="divide-y divide-border" aria-label="Reclamos y quejas">
                @forelse ($reclamos as $reclamo)
                    @php $vencido = $reclamo->estado === EstadoReclamo::Pendiente && $reclamo->fecha_limite_respuesta->copy()->endOfDay()->isPast(); @endphp
                    <li>
                        <a
                            href="{{ route('admin.reclamos.show', $reclamo) }}"
                            class="flex flex-col gap-2 px-4 py-4 transition-colors hover:bg-row-hover focus-visible:bg-row-hover sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:px-5"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-text-primary">
                                    <span class="font-mono tabular-nums">N.° {{ $reclamo->numero_correlativo }}</span> · {{ $reclamo->tipo->etiqueta() }}
                                </p>
                                <p class="truncate text-sm text-text-secondary">
                                    {{ $reclamo->nombre_consumidor }} · {{ $reclamo->descripcion_bien }}
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center gap-2 sm:gap-3">
                                <span class="text-xs tabular-nums {{ $vencido ? 'font-semibold text-rechazado-text' : 'text-text-secondary' }}">
                                    {{ $vencido ? 'Venció' : 'Vence' }} {{ $reclamo->fecha_limite_respuesta->translatedFormat('d/m/Y') }}
                                </span>
                                <x-admin.badge :tone="$reclamo->estado === EstadoReclamo::Pendiente ? 'warning' : 'success'">{{ $reclamo->estado->etiqueta() }}</x-admin.badge>
                                <x-admin.icon name="flecha-der" class="hidden h-4 w-4 text-text-secondary sm:block" />
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="p-4 sm:p-5">
                        <x-admin.empty-state
                            icon="reclamos"
                            tone="success"
                            title="Sin reclamos registrados"
                            description="Cuando un cliente use el Libro de Reclamaciones, aparecerá aquí con su plazo de respuesta."
                        />
                    </li>
                @endforelse
            </ul>

            @if ($reclamos->hasPages())
                <div class="border-t border-border p-4 sm:px-5">
                    {{ $reclamos->links('admin.partials.paginacion') }}
                </div>
            @endif
        </x-admin.card>
    </div>
</x-layouts.admin-dashboard>
