<x-layouts.admin-dashboard :titulo="'Productos eliminados - Enlix Admin'" :sin-scroll="true">
    <div
        x-data="{
            confirmacion: { abierta: false, titulo: '', mensaje: '', textoBoton: '', accion: null },
            pedirConfirmacion({ titulo, mensaje, textoBoton, accion }) {
                this.confirmacion = { abierta: true, titulo, mensaje, textoBoton, accion };
            },
            confirmarAccion() {
                const accion = this.confirmacion.accion;
                this.confirmacion.abierta = false;
                if (accion) accion();
            },
        }"
        class="mx-auto flex w-full max-w-6xl flex-col gap-4 md:h-full md:min-h-0 md:gap-6"
    >
        <div class="flex shrink-0 flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-text-primary md:text-[28px]">Productos eliminados</h1>
                <p class="text-sm text-text-caption">{{ trans_choice('1 producto en la papelera|:count productos en la papelera', $productos->count()) }}</p>
            </div>
            <a href="{{ route('admin.productos.index') }}" class="inline-flex h-10 items-center justify-center rounded-nav border border-border bg-card px-4 text-sm font-medium text-text-primary hover:bg-page">
                ← Volver a Productos
            </a>
        </div>

        @if (session('exito'))
            <div class="shrink-0 rounded-nav bg-pagado-bg px-4 py-3 text-sm font-medium text-pagado-text">
                {{ session('exito') }}
            </div>
        @endif

        <x-admin.card :padded="false" class="md:min-h-0 md:flex-1" scroll>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-border text-xs text-text-caption">
                            <th class="sticky top-0 z-10 bg-card py-3 pl-5 pr-3 font-medium">Producto</th>
                            <th class="sticky top-0 z-10 bg-card py-3 pr-3 font-medium">Referencia</th>
                            <th class="sticky top-0 z-10 bg-card py-3 pr-3 font-medium">Categoría</th>
                            <th class="sticky top-0 z-10 bg-card py-3 pr-3 font-medium">Eliminado</th>
                            <th class="sticky top-0 z-10 bg-card py-3 pr-5 text-right font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($productos as $producto)
                            <tr>
                                <td class="py-2.5 pl-5 pr-3 font-medium text-text-primary">{{ $producto->nombre }}</td>
                                <td class="py-2.5 pr-3 font-mono text-xs text-text-caption">{{ $producto->referencia }}</td>
                                <td class="py-2.5 pr-3 text-text-secondary">{{ $producto->categoria?->nombre ?? 'Sin categoría' }}</td>
                                <td class="whitespace-nowrap py-2.5 pr-3 font-mono text-xs text-text-caption">hace {{ $producto->deleted_at->locale('es')->diffForHumans(null, true) }}</td>
                                <td class="whitespace-nowrap py-2.5 pr-5 text-right">
                                    {{--
                                        @submit de Alpine, no onsubmit nativo: el CSP del
                                        sitio no tiene 'unsafe-inline' en script-src y lo
                                        bloquearia en silencio (ver _fila.blade.php).
                                    --}}
                                    <form method="POST" action="{{ route('admin.productos.restaurar', $producto) }}" class="inline" x-ref="{{ 'formRestaurar'.$producto->id }}">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="button"
                                            class="text-xs font-medium text-accent hover:text-accent-hover"
                                            @click="pedirConfirmacion({
                                                titulo: 'Recuperar producto',
                                                mensaje: {{ Illuminate\Support\Js::from('¿Recuperar "'.$producto->nombre.'"? Vuelve a la lista de productos, pero queda inactivo hasta que lo actives.') }},
                                                textoBoton: 'Recuperar',
                                                accion: () => $refs.{{ 'formRestaurar'.$producto->id }}.submit(),
                                            })"
                                        >
                                            Recuperar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8">
                                    <x-admin.empty-state title="No hay productos eliminados." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>

        {{-- Confirmación de "Recuperar", mismo patrón que Eliminar/Activar/Desactivar en el listado principal. --}}
        <div x-show="confirmacion.abierta" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="confirmacion.abierta = false"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-card p-5 shadow-xl">
                <h3 class="mb-2 text-sm font-semibold text-text-primary" x-text="confirmacion.titulo"></h3>
                <p class="mb-4 text-sm text-text-secondary" x-text="confirmacion.mensaje"></p>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="confirmacion.abierta = false" class="inline-flex h-9 items-center justify-center rounded-nav border border-border px-3 text-sm font-medium text-text-secondary hover:bg-page">Cancelar</button>
                    <button type="button" @click="confirmarAccion()" class="inline-flex h-9 items-center justify-center rounded-nav bg-accent px-3 text-sm font-medium text-white hover:bg-accent-hover" x-text="confirmacion.textoBoton"></button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin-dashboard>
