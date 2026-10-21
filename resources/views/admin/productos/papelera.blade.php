<x-layouts.admin-dashboard :titulo="'Productos eliminados - Enlix Admin'" :sin-scroll="true">
    <div
        x-data="papeleraApp({{ $productos->count() }})"
        @keydown.escape.window="confirmacion.abierta = false"
        class="mx-auto flex w-full max-w-6xl flex-col gap-4 md:h-full md:min-h-0 md:gap-6"
    >
        <div class="flex shrink-0 flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-text-primary md:text-[28px]">Productos eliminados</h1>
                <p class="text-sm text-text-caption" x-text="total === 1 ? '1 producto en la papelera' : total + ' productos en la papelera'">{{ trans_choice('1 producto en la papelera|:count productos en la papelera', $productos->count()) }}</p>
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
                        @foreach ($productos as $producto)
                            <tr id="fila-papelera-{{ $producto->id }}">
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
                                            class="inline-flex min-h-[44px] items-center px-1.5 text-xs font-medium text-accent hover:text-accent-hover sm:min-h-0"
                                            @click="pedirConfirmacion({
                                                titulo: 'Recuperar producto',
                                                mensaje: {{ Illuminate\Support\Js::from('¿Recuperar "'.$producto->nombre.'"? Vuelve a la lista de productos, pero queda inactivo hasta que lo actives.') }},
                                                textoBoton: 'Recuperar',
                                                accion: () => recuperar($refs.{{ 'formRestaurar'.$producto->id }}, {{ $producto->id }}),
                                            })"
                                        >
                                            Recuperar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        <tr x-show="total === 0" @if ($productos->isNotEmpty()) x-cloak @endif>
                            <td colspan="5" class="py-8">
                                <x-admin.empty-state title="No hay productos eliminados." />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-admin.card>

        {{-- Confirmación de "Recuperar", mismo patrón que Eliminar/Activar/Desactivar en el listado principal. --}}
        <div x-show="confirmacion.abierta" x-cloak class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/40" @click="confirmacion.abierta = false"></div>
            <div class="relative w-full rounded-t-2xl bg-card p-5 shadow-xl sm:max-w-sm sm:rounded-2xl">
                <h3 class="mb-2 text-sm font-semibold text-text-primary" x-text="confirmacion.titulo"></h3>
                <p class="mb-4 text-sm text-text-secondary" x-text="confirmacion.mensaje"></p>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3">
                    <button type="button" @click="confirmacion.abierta = false" class="inline-flex min-h-[44px] items-center justify-center rounded-nav border border-border px-3 text-sm font-medium text-text-secondary hover:bg-page sm:h-9 sm:min-h-0">Cancelar</button>
                    <button type="button" @click="confirmarAccion()" :disabled="enCurso" class="inline-flex min-h-[44px] items-center justify-center rounded-nav bg-accent px-3 text-sm font-medium text-white hover:bg-accent-hover disabled:opacity-60 sm:h-9 sm:min-h-0" x-text="confirmacion.textoBoton"></button>
                </div>
            </div>
        </div>

        <div x-show="toast.mensaje" x-cloak x-transition role="status" aria-live="polite" class="fixed inset-x-4 bottom-4 z-50 rounded-nav px-4 py-3 text-sm font-medium text-white shadow-lg sm:inset-x-auto sm:bottom-6 sm:right-6" :class="toast.error ? 'bg-danger-strong' : 'bg-sidebar-active'" x-text="toast.mensaje"></div>
    </div>

    @push('scripts')
        <script nonce="{{ $cspNonce }}">
            function papeleraApp(totalInicial) {
                return {
                    total: totalInicial,
                    enCurso: false,
                    confirmacion: { abierta: false, titulo: '', mensaje: '', textoBoton: '', accion: null },
                    toast: { mensaje: null, error: false },

                    pedirConfirmacion({ titulo, mensaje, textoBoton, accion }) {
                        this.confirmacion = { abierta: true, titulo, mensaje, textoBoton, accion };
                    },

                    async confirmarAccion() {
                        const accion = this.confirmacion.accion;
                        if (!accion || this.enCurso) {
                            return;
                        }
                        this.enCurso = true;
                        try {
                            await accion();
                        } finally {
                            this.enCurso = false;
                            this.confirmacion.abierta = false;
                        }
                    },

                    /** Por fetch; si la red falla, el form clásico (redirect con mensaje). */
                    async recuperar(formulario, id) {
                        let respuesta;
                        try {
                            respuesta = await fetch(formulario.action, {
                                method: 'POST',
                                headers: {
                                    Accept: 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: new FormData(formulario),
                            });
                        } catch (e) {
                            formulario.submit();
                            return;
                        }
                        const datos = await respuesta.json().catch(() => ({}));
                        if (!respuesta.ok) {
                            this.mostrarToast(respuesta.status === 419 ? 'Tu sesión expiró. Recarga la página.' : 'No se pudo recuperar el producto.', true);
                            return;
                        }
                        document.getElementById(`fila-papelera-${id}`)?.remove();
                        this.total = Math.max(0, this.total - 1);
                        this.mostrarToast(datos.mensaje || 'Producto recuperado.');
                    },

                    mostrarToast(mensaje, error = false) {
                        this.toast = { mensaje, error };
                        setTimeout(() => {
                            this.toast.mensaje = null;
                        }, 3500);
                    },
                };
            }
        </script>
    @endpush
</x-layouts.admin-dashboard>
