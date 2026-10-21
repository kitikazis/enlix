<x-layouts.admin-dashboard :titulo="'Productos eliminados - Enlix Admin'" :sin-scroll="true">
    <div
        x-data="papeleraApp({{ $productos->count() }})"
        @keydown.escape.window="confirmacion.abierta = false"
        class="flex w-full flex-col gap-5 sm:gap-6 lg:min-h-0 lg:flex-1"
    >
        <x-admin.page-header title="Productos eliminados" :back="route('admin.productos.index')" back-label="Productos">
            <x-slot:subtitle>
                <span x-text="total === 1 ? '1 producto en la papelera' : total + ' productos en la papelera'">{{ trans_choice('1 producto en la papelera|:count productos en la papelera', $productos->count()) }}</span>
            </x-slot:subtitle>
        </x-admin.page-header>

        @if (session('exito'))
            <div class="shrink-0 rounded-nav bg-pagado-bg px-4 py-3 text-sm font-medium text-pagado-text" role="status">
                {{ session('exito') }}
            </div>
        @endif

        <x-admin.card :padded="false" class="lg:min-h-0 lg:flex-1" fill>
            <x-admin.data-list
                caption="Productos en la papelera"
                scroll="fill"
                :columns="['Producto', 'Referencia', 'Categoría', 'Eliminado', ['label' => 'Acciones', 'sr' => true]]"
            >
                @foreach ($productos as $producto)
                    <tr id="fila-papelera-{{ $producto->id }}">
                        <td data-dl="title" class="font-medium text-text-primary">{{ $producto->nombre }}</td>
                        <td data-label="Referencia" class="whitespace-nowrap font-mono text-xs text-text-secondary">{{ $producto->referencia }}</td>
                        <td data-label="Categoría" class="text-text-secondary">{{ $producto->categoria?->nombre ?? 'Sin categoría' }}</td>
                        <td data-label="Eliminado" class="whitespace-nowrap tabular-nums text-text-secondary">hace {{ $producto->deleted_at->locale('es')->diffForHumans(null, true) }}</td>
                        <td data-dl="actions" data-align="right" class="whitespace-nowrap">
                            {{--
                                @click de Alpine, no onsubmit nativo: el CSP del
                                sitio no tiene 'unsafe-inline' en script-src y lo
                                bloquearia en silencio (ver _fila.blade.php).
                            --}}
                            <form method="POST" action="{{ route('admin.productos.restaurar', $producto) }}" class="max-lg:w-full" x-ref="{{ 'formRestaurar'.$producto->id }}">
                                @csrf
                                @method('PATCH')
                                <button
                                    type="button"
                                    class="btn btn-secondary btn-sm text-accent max-lg:w-full lg:border-transparent lg:bg-transparent lg:hover:bg-page"
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
                    <td colspan="5">
                        <x-admin.empty-state
                            icon="papelera"
                            title="La papelera está vacía"
                            description="Los productos que elimines aparecen aquí y se pueden recuperar."
                            :action-url="route('admin.productos.index')"
                            action-label="Volver a Productos"
                        />
                    </td>
                </tr>
            </x-admin.data-list>
        </x-admin.card>

        {{-- Confirmación de "Recuperar", mismo patrón que Eliminar/Activar/Desactivar en el listado principal. --}}
        <div x-show="confirmacion.abierta" x-cloak class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/40" @click="confirmacion.abierta = false"></div>
            <div class="relative w-full rounded-t-2xl bg-card p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] shadow-pop sm:max-w-sm sm:rounded-2xl sm:pb-5">
                <h3 class="mb-2 text-base font-semibold text-text-primary" x-text="confirmacion.titulo"></h3>
                <p class="mb-4 text-sm text-text-secondary" x-text="confirmacion.mensaje"></p>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3">
                    <button type="button" @click="confirmacion.abierta = false" class="btn btn-secondary">Cancelar</button>
                    <button type="button" @click="confirmarAccion()" :disabled="enCurso" :aria-busy="enCurso ? 'true' : 'false'" class="btn btn-primary" x-text="confirmacion.textoBoton"></button>
                </div>
            </div>
        </div>

        <div x-show="toast.mensaje" x-cloak x-transition role="status" aria-live="polite" class="fixed inset-x-4 bottom-[calc(var(--spacing-bottomnav)+env(safe-area-inset-bottom)+0.75rem)] z-[75] rounded-nav px-4 py-3 text-sm font-medium text-white shadow-pop md:inset-x-auto md:bottom-6 md:right-6" :class="toast.error ? 'bg-danger-strong' : 'bg-sidebar-active'" x-text="toast.mensaje"></div>
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
