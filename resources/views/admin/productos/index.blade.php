<x-layouts.admin-dashboard :titulo="'Productos - Enlix Admin'" :sin-scroll="true">

    @push('head')
        {{-- jsdelivr, no cdnjs: es el unico CDN de terceros que el CSP
             (app/Http/Middleware/SecurityHeaders.php) ya permite en
             script-src/style-src. cdnjs.cloudflare.com no esta en la lista
             y el navegador bloqueaba silenciosamente el CSS y el JS de
             Quill, dejando el editor (y el resto del modal, por el error
             de Alpine al no existir `Quill`) roto. --}}
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.snow.css">
        <script src="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.min.js"></script>
    @endpush

    <div
        x-data="productosApp(@js($categorias->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->nombre])->all()))"
        class="mx-auto flex w-full max-w-6xl flex-col gap-4 md:h-full md:min-h-0 md:gap-6"
    >

        <div class="flex shrink-0 flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <h1 class="text-2xl font-semibold text-text-primary md:text-[28px]">Productos</h1>
            <button type="button" @click="abrirCrear()" class="inline-flex h-10 items-center justify-center rounded-nav bg-accent px-4 text-sm font-medium text-white hover:bg-accent-hover">
                + Nuevo producto
            </button>
        </div>

        @if (session('exito'))
            <div class="shrink-0 rounded-nav bg-pagado-bg px-4 py-3 text-sm font-medium text-pagado-text">
                {{ session('exito') }}
            </div>
        @endif

        <div class="flex shrink-0 flex-col gap-3 sm:flex-row sm:items-center">
            <input
                type="text"
                x-model="busqueda"
                @input="filtrar()"
                placeholder="Buscar por producto o referencia…"
                class="h-10 w-full rounded-nav border border-border bg-card px-3 text-sm sm:max-w-xs"
            >
            <select x-model="filtroCategoria" @change="filtrar()" class="h-10 w-full rounded-nav border border-border bg-card px-3 text-sm sm:max-w-xs">
                <option value="">Todas las categorías</option>
                <template x-for="cat in categorias" :key="cat.id">
                    <option :value="String(cat.id)" x-text="cat.nombre"></option>
                </template>
            </select>
        </div>

        <x-admin.card :padded="false" class="md:min-h-0 md:flex-1" scroll>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-border text-xs text-text-caption">
                            <th class="sticky top-0 z-10 bg-card py-3 pl-5 pr-3 font-medium">Imagen</th>
                            <th class="sticky top-0 z-10 bg-card py-3 pr-3 font-medium">Producto</th>
                            <th class="sticky top-0 z-10 bg-card py-3 pr-3 font-medium">Referencia</th>
                            <th class="sticky top-0 z-10 bg-card py-3 pr-3 font-medium">Categoría</th>
                            <th class="sticky top-0 z-10 bg-card py-3 pr-3 font-medium">Especificaciones</th>
                            <th class="sticky top-0 z-10 bg-card py-3 pr-3 font-medium">Estado</th>
                            <th class="sticky top-0 z-10 bg-card py-3 pr-5 text-right font-medium"></th>
                        </tr>
                    </thead>
                    <tbody id="tabla-productos-cuerpo" class="divide-y divide-border">
                        @forelse ($productos as $producto)
                            @include('admin.productos._fila', ['producto' => $producto])
                        @empty
                            <tr id="fila-vacia">
                                <td colspan="7" class="py-8">
                                    <x-admin.empty-state title="No hay productos todavía." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>

        {{-- Modal de producto --}}
        <div x-show="modalAbierto" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="cerrarModal()"></div>

            <div class="relative flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-card shadow-xl">
                <div class="flex items-center justify-between border-b border-border px-6 py-4">
                    <h2 class="text-lg font-semibold text-text-primary" x-text="modoEdicion ? 'Editar producto' : 'Nuevo producto'"></h2>
                    <button type="button" @click="cerrarModal()" class="flex h-8 w-8 items-center justify-center rounded-nav text-text-caption hover:bg-page" aria-label="Cerrar">✕</button>
                </div>

                <div class="flex gap-1 border-b border-border px-6 pt-3">
                    <template x-for="tab in [['general','General'],['descripcion','Descripción'],['especificaciones','Especificaciones']]" :key="tab[0]">
                        <button
                            type="button"
                            @click="tabActiva = tab[0]"
                            class="relative flex items-center gap-1.5 rounded-t-nav px-3 py-2 text-sm font-medium"
                            :class="tabActiva === tab[0] ? 'border-b-2 border-accent text-accent' : 'text-text-secondary hover:text-text-primary'"
                        >
                            <span x-text="tab[1]"></span>
                            <span x-show="tabTieneError(tab[0])" class="h-1.5 w-1.5 rounded-full bg-rechazado-dot"></span>
                        </button>
                    </template>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-5">

                    {{-- Tab: General --}}
                    <div x-show="tabActiva === 'general'" class="flex flex-col gap-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-text-primary">Producto</label>
                                <input
                                    type="text" x-model="form.nombre" @input="onNombreInput()"
                                    maxlength="150" required
                                    class="h-10 w-full rounded-nav border border-border bg-card px-3 text-sm"
                                >
                                <p class="mt-1 text-xs text-rechazado-text" x-show="errores.nombre" x-text="errores.nombre?.[0]"></p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-text-primary">Slug <span class="font-normal text-text-caption">(editable)</span></label>
                                <input
                                    type="text" x-model="form.slug" @input="onSlugInput()"
                                    maxlength="170"
                                    class="h-10 w-full rounded-nav border border-border bg-card px-3 font-mono text-sm"
                                >
                                <p class="mt-1 text-xs text-rechazado-text" x-show="errores.slug" x-text="errores.slug?.[0]"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-text-primary">Referencia</label>
                                <input
                                    type="text" x-model="form.referencia" @input="verificarReferencia()"
                                    maxlength="50" required
                                    class="h-10 w-full rounded-nav border border-border bg-card px-3 text-sm"
                                >
                                <p class="mt-1 text-xs" :class="referenciaEstado === 'ocupada' ? 'text-rechazado-text' : 'text-pagado-text'" x-show="referenciaEstado === 'ocupada' || referenciaEstado === 'disponible'">
                                    <span x-show="referenciaEstado === 'ocupada'">Esa referencia ya está en uso.</span>
                                    <span x-show="referenciaEstado === 'disponible'">Referencia disponible.</span>
                                </p>
                                <p class="mt-1 text-xs text-rechazado-text" x-show="errores.referencia" x-text="errores.referencia?.[0]"></p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-text-primary">Categoría</label>
                                <div class="flex gap-2">
                                    <select x-model="form.categoria_id" class="h-10 w-full rounded-nav border border-border bg-card px-3 text-sm">
                                        <option value="">Selecciona una categoría</option>
                                        <template x-for="cat in categorias" :key="cat.id">
                                            <option :value="cat.id" x-text="cat.nombre"></option>
                                        </template>
                                    </select>
                                    <button type="button" @click="abrirCategoriaModal()" title="Crear categoría" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-nav border border-border text-text-secondary hover:bg-page">+</button>
                                </div>
                                <p class="mt-1 text-xs text-rechazado-text" x-show="errores.categoria_id" x-text="errores.categoria_id?.[0]"></p>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-text-primary">Imagen del producto</label>
                            <div
                                @dragover.prevent @drop.prevent="procesarArchivo($event.dataTransfer.files[0])"
                                @click="$refs.inputImagen.click()"
                                class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-nav border-2 border-dashed border-border bg-page px-4 py-6 text-center"
                            >
                                <template x-if="imagenPreviewUrl">
                                    <img :src="imagenPreviewUrl" alt="Vista previa" class="h-28 w-28 rounded-nav object-cover">
                                </template>
                                <template x-if="!imagenPreviewUrl">
                                    <p class="text-sm text-text-caption">Arrastra una imagen aquí o haz clic para seleccionarla<br>JPG, PNG o WEBP · máx. 2 MB</p>
                                </template>
                                <input x-ref="inputImagen" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @click.stop @change="procesarArchivo($event.target.files[0])">
                            </div>
                            <div class="mt-2 flex gap-3" x-show="imagenPreviewUrl">
                                <button type="button" @click.stop="$refs.inputImagen.click()" class="text-xs font-medium text-accent hover:text-accent-hover">Cambiar</button>
                                <button
                                    type="button" @click.stop="quitarImagen()"
                                    class="text-xs font-medium text-rechazado-text hover:underline"
                                    x-text="imagenArchivo ? 'Deshacer' : 'Eliminar imagen'"
                                ></button>
                            </div>
                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-page" x-show="guardando && subiendoProgreso > 0">
                                <div class="h-full bg-accent transition-all" :style="`width: ${subiendoProgreso}%`"></div>
                            </div>
                            <p class="mt-1 text-xs text-rechazado-text" x-show="errores.imagen" x-text="errores.imagen?.[0]"></p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-text-primary">Precio (S/)</label>
                                <input type="number" x-model="form.precio" step="0.01" min="0.01" max="99999.99" required class="h-10 w-full rounded-nav border border-border bg-card px-3 text-sm">
                                <p class="mt-1 text-xs text-rechazado-text" x-show="errores.precio" x-text="errores.precio?.[0]"></p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-text-primary">Stock</label>
                                <input type="number" x-model="form.stock" min="0" class="h-10 w-full rounded-nav border border-border bg-card px-3 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-text-primary">Orden</label>
                                <input type="number" x-model="form.orden" min="0" class="h-10 w-full rounded-nav border border-border bg-card px-3 text-sm">
                            </div>
                        </div>

                        <div class="flex gap-6">
                            <label class="flex items-center gap-2 text-sm text-text-primary">
                                <input type="checkbox" x-model="form.activo" class="h-4 w-4 rounded border-border">
                                Visible en /productos
                            </label>
                            <label class="flex items-center gap-2 text-sm text-text-primary">
                                <input type="checkbox" x-model="form.destacado" class="h-4 w-4 rounded border-border">
                                Destacado
                            </label>
                        </div>
                    </div>

                    {{-- Tab: Descripción --}}
                    <div x-show="tabActiva === 'descripcion'" class="flex flex-col gap-2">
                        <label class="mb-1 block text-sm font-medium text-text-primary">Descripción</label>
                        <div x-ref="editor" class="min-h-[220px] bg-card"></div>
                        <p class="mt-1 text-xs text-rechazado-text" x-show="errores.descripcion" x-text="errores.descripcion?.[0]"></p>
                    </div>

                    {{-- Tab: Especificaciones --}}
                    <div x-show="tabActiva === 'especificaciones'" class="flex flex-col gap-3">
                        <p class="text-xs text-text-caption">Pares clave / valor, ej. "Material" → "Acero inoxidable". Arrastra <span aria-hidden="true">⠿</span> para reordenar.</p>

                        <template x-for="(fila, i) in especificaciones" :key="i">
                            <div
                                draggable="true"
                                @dragstart="arrastrando = i"
                                @dragover.prevent
                                @drop="moverFila(arrastrando, i)"
                                class="flex items-center gap-2 rounded-nav border border-border bg-page p-2"
                            >
                                <span class="cursor-move px-1 text-text-caption" aria-hidden="true">⠿</span>
                                <input type="text" x-model="fila.clave" placeholder="Clave" class="h-9 w-1/3 rounded-nav border border-border bg-card px-2 text-sm">
                                <input type="text" x-model="fila.valor" placeholder="Valor" class="h-9 flex-1 rounded-nav border border-border bg-card px-2 text-sm">
                                <button type="button" @click="especificaciones.splice(i, 1)" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-nav text-text-caption hover:bg-card" aria-label="Eliminar fila">✕</button>
                            </div>
                        </template>

                        <button type="button" @click="especificaciones.push({ clave: '', valor: '' })" class="inline-flex h-9 w-fit items-center justify-center rounded-nav border border-border px-3 text-sm font-medium text-text-secondary hover:bg-page">
                            + Agregar fila
                        </button>
                    </div>

                </div>

                <div class="flex items-center justify-end gap-3 border-t border-border px-6 py-4">
                    <button type="button" @click="cerrarModal()" class="inline-flex h-10 items-center justify-center rounded-nav border border-border px-4 text-sm font-medium text-text-secondary hover:bg-page">Cancelar</button>
                    <button type="button" @click="guardar()" :disabled="guardando" class="inline-flex h-10 items-center justify-center rounded-nav bg-accent px-4 text-sm font-medium text-white hover:bg-accent-hover disabled:opacity-60">
                        <span x-text="guardando ? 'Guardando…' : 'Guardar'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal anidado: crear categoría rápida. El fondo va casi transparente
             porque se abre sobre el modal de producto, que ya oscureció la
             página con su propio bg-black/40 (sumar otro bg-black/50 dejaba
             todo casi negro). --}}
        <div x-show="categoriaModalAbierto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/10" @click="categoriaModalAbierto = false"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-card p-5 shadow-xl">
                <h3 class="mb-3 text-sm font-semibold text-text-primary">Nueva categoría</h3>
                <input
                    type="text" x-model="categoriaNombre" @keydown.enter="crearCategoria()"
                    placeholder="Nombre de la categoría" maxlength="100"
                    class="h-10 w-full rounded-nav border border-border bg-card px-3 text-sm"
                >
                <p class="mt-1 text-xs text-rechazado-text" x-show="categoriaError" x-text="categoriaError"></p>
                <div class="mt-4 flex justify-end gap-3">
                    <button type="button" @click="categoriaModalAbierto = false" class="inline-flex h-9 items-center justify-center rounded-nav border border-border px-3 text-sm font-medium text-text-secondary hover:bg-page">Cancelar</button>
                    <button type="button" @click="crearCategoria()" :disabled="categoriaGuardando" class="inline-flex h-9 items-center justify-center rounded-nav bg-accent px-3 text-sm font-medium text-white hover:bg-accent-hover disabled:opacity-60">Crear</button>
                </div>
            </div>
        </div>

        {{-- Lightbox de imagen --}}
        <div x-show="lightboxUrl" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-6" @click.self="lightboxUrl = null">
            <img :src="lightboxUrl" alt="" class="max-h-full max-w-full rounded-nav">
        </div>

        {{-- Toast --}}
        <div x-show="toastMensaje" x-cloak x-transition class="fixed bottom-6 right-6 z-50 rounded-nav px-4 py-3 text-sm font-medium text-white shadow-lg" :class="toastError ? 'bg-danger-strong' : 'bg-sidebar-active'" x-text="toastMensaje"></div>
    </div>

    @push('scripts')
        <script nonce="{{ $cspNonce }}">
            function productosApp(categoriasIniciales) {
                const rutaProductos = @js(route('admin.productos.index'));
                const rutaVerificarReferencia = @js(route('admin.productos.verificar-referencia'));
                const rutaCategorias = @js(route('admin.categorias.store'));
                const camposPorTab = {
                    general: ['categoria_id', 'referencia', 'nombre', 'slug', 'imagen', 'precio', 'stock', 'orden'],
                    descripcion: ['descripcion'],
                    especificaciones: ['especificaciones'],
                };

                return {
                    categorias: categoriasIniciales,
                    busqueda: '',
                    filtroCategoria: '',

                    modalAbierto: false,
                    modoEdicion: false,
                    productoId: null,
                    tabActiva: 'general',
                    guardando: false,
                    subiendoProgreso: 0,
                    errores: {},
                    slugTocado: false,

                    form: this.formVacio(),
                    especificaciones: [],
                    arrastrando: null,

                    imagenArchivo: null,
                    imagenPreviewUrl: null,
                    imagenExistenteUrl: null,
                    eliminarImagenExistente: false,

                    referenciaEstado: null,
                    referenciaCheckTimer: null,

                    categoriaModalAbierto: false,
                    categoriaNombre: '',
                    categoriaGuardando: false,
                    categoriaError: null,

                    lightboxUrl: null,
                    toastMensaje: null,
                    toastError: false,

                    quill: null,

                    init() {
                        // Si Quill no cargo (CDN caido, bloqueador de
                        // anuncios, etc.) esto NO debe tumbar el resto del
                        // componente: guardar/editar/toast siguen
                        // funcionando sin el editor de texto enriquecido.
                        try {
                            this.quill = new Quill(this.$refs.editor, {
                                theme: 'snow',
                                modules: {
                                    toolbar: [
                                        ['bold', 'italic'],
                                        [{ list: 'ordered' }, { list: 'bullet' }],
                                        ['link'],
                                        ['clean'],
                                    ],
                                },
                            });
                        } catch (e) {
                            console.error('No se pudo inicializar el editor de descripcion (Quill):', e);
                        }
                    },

                    formVacio() {
                        return {
                            categoria_id: '',
                            referencia: '',
                            nombre: '',
                            slug: '',
                            descripcion: '',
                            precio: '',
                            stock: 0,
                            orden: 0,
                            activo: true,
                            destacado: false,
                        };
                    },

                    csrfToken() {
                        return document.querySelector('meta[name="csrf-token"]').content;
                    },

                    filtrar() {
                        const q = this.busqueda.trim().toLowerCase();
                        const cat = this.filtroCategoria;
                        document.querySelectorAll('#tabla-productos-cuerpo tr[data-id]').forEach((fila) => {
                            const coincideTexto = !q || fila.dataset.nombre.includes(q) || fila.dataset.referencia.includes(q);
                            const coincideCategoria = !cat || fila.dataset.categoriaId === cat;
                            fila.style.display = (coincideTexto && coincideCategoria) ? '' : 'none';
                        });
                    },

                    abrirCrear() {
                        this.modoEdicion = false;
                        this.productoId = null;
                        this.errores = {};
                        this.tabActiva = 'general';
                        this.slugTocado = false;
                        this.form = this.formVacio();
                        this.especificaciones = [];
                        this.imagenArchivo = null;
                        this.imagenPreviewUrl = null;
                        this.imagenExistenteUrl = null;
                        this.eliminarImagenExistente = false;
                        this.referenciaEstado = null;
                        if (this.quill) {
                            this.quill.setText('');
                        }
                        this.modalAbierto = true;
                    },

                    async abrirEditar(id) {
                        this.errores = {};
                        this.tabActiva = 'general';
                        this.modoEdicion = true;
                        this.productoId = id;
                        this.slugTocado = true;
                        this.referenciaEstado = null;
                        this.eliminarImagenExistente = false;
                        this.modalAbierto = true;

                        const respuesta = await fetch(`${rutaProductos}/${id}`, { headers: { Accept: 'application/json' } });
                        const datos = await respuesta.json();

                        this.form = {
                            categoria_id: datos.categoria_id ?? '',
                            referencia: datos.referencia,
                            nombre: datos.nombre,
                            slug: datos.slug,
                            descripcion: datos.descripcion,
                            precio: datos.precio,
                            stock: datos.stock,
                            orden: datos.orden,
                            activo: datos.activo,
                            destacado: datos.destacado,
                        };
                        this.especificaciones = (datos.especificaciones || []).map((f) => ({ clave: f.clave, valor: f.valor }));
                        this.imagenArchivo = null;
                        this.imagenPreviewUrl = datos.tiene_imagen ? datos.imagen_url : null;
                        this.imagenExistenteUrl = this.imagenPreviewUrl;

                        if (this.quill) {
                            this.quill.setText('');
                            this.quill.clipboard.dangerouslyPasteHTML(datos.descripcion || '');
                        }
                    },

                    cerrarModal() {
                        this.modalAbierto = false;
                    },

                    onNombreInput() {
                        if (!this.slugTocado) {
                            this.form.slug = this.slugify(this.form.nombre);
                        }
                    },

                    onSlugInput() {
                        this.slugTocado = true;
                    },

                    slugify(texto) {
                        return (texto || '')
                            .normalize('NFD').replace(/[̀-ͯ]/g, '')
                            .toLowerCase()
                            .trim()
                            .replace(/[^a-z0-9\s-]/g, '')
                            .replace(/[\s_-]+/g, '-')
                            .replace(/^-+|-+$/g, '');
                    },

                    moverFila(desde, hasta) {
                        if (desde === null || desde === hasta) {
                            return;
                        }
                        const fila = this.especificaciones.splice(desde, 1)[0];
                        this.especificaciones.splice(hasta, 0, fila);
                        this.arrastrando = null;
                    },

                    procesarArchivo(archivo) {
                        if (!archivo) {
                            return;
                        }
                        const tiposValidos = ['image/jpeg', 'image/png', 'image/webp'];
                        if (!tiposValidos.includes(archivo.type)) {
                            this.errores = { ...this.errores, imagen: ['Formato no permitido. Usa JPG, PNG o WEBP.'] };
                            return;
                        }
                        if (archivo.size > 2 * 1024 * 1024) {
                            this.errores = { ...this.errores, imagen: ['La imagen no debe superar 2 MB.'] };
                            return;
                        }
                        const { imagen, ...resto } = this.errores;
                        this.errores = resto;
                        this.imagenArchivo = archivo;
                        this.imagenPreviewUrl = URL.createObjectURL(archivo);
                        this.eliminarImagenExistente = false;
                    },

                    /**
                     * Con un archivo nuevo ya seleccionado, lo cancela y
                     * vuelve a la imagen guardada. Sin archivo nuevo (se ve
                     * la imagen guardada tal cual), la elimina de verdad: al
                     * guardar, el backend borra el archivo y el producto
                     * queda sin imagen.
                     */
                    quitarImagen() {
                        if (this.imagenArchivo) {
                            this.imagenArchivo = null;
                            this.imagenPreviewUrl = this.imagenExistenteUrl || null;
                            return;
                        }
                        this.imagenPreviewUrl = null;
                        this.eliminarImagenExistente = true;
                    },

                    verificarReferencia() {
                        clearTimeout(this.referenciaCheckTimer);
                        const valor = this.form.referencia.trim();
                        if (!valor) {
                            this.referenciaEstado = null;
                            return;
                        }
                        this.referenciaEstado = 'verificando';
                        this.referenciaCheckTimer = setTimeout(async () => {
                            const params = new URLSearchParams({ referencia: valor });
                            if (this.modoEdicion && this.productoId) {
                                params.set('producto_id', this.productoId);
                            }
                            try {
                                const respuesta = await fetch(`${rutaVerificarReferencia}?${params}`, { headers: { Accept: 'application/json' } });
                                const datos = await respuesta.json();
                                this.referenciaEstado = datos.disponible ? 'disponible' : 'ocupada';
                            } catch (e) {
                                this.referenciaEstado = null;
                            }
                        }, 400);
                    },

                    abrirCategoriaModal() {
                        this.categoriaNombre = '';
                        this.categoriaError = null;
                        this.categoriaModalAbierto = true;
                    },

                    async crearCategoria() {
                        if (!this.categoriaNombre.trim()) {
                            this.categoriaError = 'Escribe un nombre.';
                            return;
                        }
                        this.categoriaGuardando = true;
                        this.categoriaError = null;
                        try {
                            const respuesta = await fetch(rutaCategorias, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    Accept: 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken(),
                                },
                                body: JSON.stringify({ nombre: this.categoriaNombre }),
                            });
                            const datos = await respuesta.json();
                            if (!respuesta.ok) {
                                this.categoriaError = datos.errors?.nombre?.[0] || 'No se pudo crear la categoría.';
                                return;
                            }
                            this.categorias.push({ id: datos.id, nombre: datos.nombre });
                            this.form.categoria_id = datos.id;
                            this.categoriaModalAbierto = false;
                        } finally {
                            this.categoriaGuardando = false;
                        }
                    },

                    tabTieneError(tab) {
                        return (camposPorTab[tab] || []).some((campo) => this.errores[campo]);
                    },

                    primeraTabConError() {
                        for (const tab of Object.keys(camposPorTab)) {
                            if (this.tabTieneError(tab)) {
                                return tab;
                            }
                        }
                        return this.tabActiva;
                    },

                    guardar() {
                        if (this.quill) {
                            this.form.descripcion = this.quill.root.innerHTML;
                        }
                        this.errores = {};
                        this.guardando = true;
                        this.subiendoProgreso = 0;

                        const datos = new FormData();
                        datos.append('categoria_id', this.form.categoria_id);
                        datos.append('referencia', this.form.referencia);
                        datos.append('nombre', this.form.nombre);
                        datos.append('slug', this.form.slug || '');
                        datos.append('descripcion', this.form.descripcion);
                        datos.append('especificaciones', JSON.stringify(this.especificaciones));
                        datos.append('precio', this.form.precio);
                        datos.append('stock', this.form.stock);
                        datos.append('orden', this.form.orden);
                        datos.append('activo', this.form.activo ? '1' : '0');
                        datos.append('destacado', this.form.destacado ? '1' : '0');
                        if (this.imagenArchivo) {
                            datos.append('imagen', this.imagenArchivo);
                        }
                        datos.append('eliminar_imagen', this.eliminarImagenExistente ? '1' : '0');

                        const url = this.modoEdicion ? `${rutaProductos}/${this.productoId}` : rutaProductos;
                        if (this.modoEdicion) {
                            datos.append('_method', 'PUT');
                        }

                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', url);
                        xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken());
                        xhr.setRequestHeader('Accept', 'application/json');
                        xhr.upload.onprogress = (e) => {
                            if (e.lengthComputable) {
                                this.subiendoProgreso = Math.round((e.loaded / e.total) * 100);
                            }
                        };
                        xhr.onload = () => {
                            this.guardando = false;
                            let respuesta = {};
                            try {
                                respuesta = JSON.parse(xhr.responseText);
                            } catch (e) { /* respuesta vacía o no-JSON */ }

                            if (xhr.status === 200 || xhr.status === 201) {
                                this.insertarFila(respuesta.html);
                                this.cerrarModal();
                                this.mostrarToast(respuesta.mensaje || 'Guardado.');
                            } else if (xhr.status === 422) {
                                this.errores = respuesta.errors || {};
                                this.tabActiva = this.primeraTabConError();
                            } else {
                                this.mostrarToast('Ocurrió un error al guardar. Intenta de nuevo.', true);
                            }
                        };
                        xhr.onerror = () => {
                            this.guardando = false;
                            this.mostrarToast('No se pudo conectar con el servidor.', true);
                        };
                        xhr.send(datos);
                    },

                    insertarFila(html) {
                        if (!html) {
                            return;
                        }
                        const plantilla = document.createElement('template');
                        plantilla.innerHTML = html.trim();
                        const nuevaFila = plantilla.content.firstElementChild;

                        const filaVacia = document.getElementById('fila-vacia');
                        if (filaVacia) {
                            filaVacia.remove();
                        }

                        const filaExistente = document.getElementById(nuevaFila.id);
                        if (filaExistente) {
                            filaExistente.replaceWith(nuevaFila);
                        } else {
                            document.getElementById('tabla-productos-cuerpo').appendChild(nuevaFila);
                        }
                        window.Alpine.initTree(nuevaFila);
                        this.filtrar();
                    },

                    mostrarToast(mensaje, esError = false) {
                        this.toastMensaje = mensaje;
                        this.toastError = esError;
                        setTimeout(() => {
                            this.toastMensaje = null;
                        }, 3500);
                    },
                };
            }
        </script>
    @endpush
</x-layouts.admin-dashboard>
