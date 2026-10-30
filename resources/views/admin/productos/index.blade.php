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
        x-data="productosApp(@js($categorias->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->nombre])->all()), {{ (int) $totalEliminados }})"
        @keydown.escape.window="cerrarConEscape()"
        class="flex w-full flex-col gap-5 sm:gap-6 lg:min-h-0 lg:flex-1"
    >

        <x-admin.page-header title="Productos" subtitle="Crea, edita y publica los productos del catálogo.">
            <x-slot:actions>
                <a
                    href="{{ route('admin.productos.papelera') }}" x-show="totalEliminados > 0" @if ($totalEliminados === 0) x-cloak @endif
                    class="btn btn-secondary"
                >
                    <x-admin.icon name="papelera" class="h-4 w-4" />
                    Eliminados
                    <span class="count-badge" x-text="totalEliminados">{{ $totalEliminados }}</span>
                </a>
                <button type="button" @click="abrirCrear()" class="btn btn-primary flex-1 sm:flex-none">
                    <x-admin.icon name="mas-simple" class="h-4 w-4" />
                    Nuevo producto
                </button>
            </x-slot:actions>
        </x-admin.page-header>

        @if (session('exito'))
            <div class="shrink-0 rounded-nav bg-pagado-bg px-4 py-3 text-sm font-medium text-pagado-text" role="status">
                {{ session('exito') }}
            </div>
        @endif

        <div class="grid shrink-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:flex lg:items-center" role="search">
            <div class="relative min-w-0 lg:w-80">
                <label for="buscar-producto" class="sr-only">Buscar por producto o referencia</label>
                <x-admin.icon name="buscar" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-secondary" />
                <input
                    id="buscar-producto"
                    type="search"
                    x-model="busqueda"
                    @input="filtrar()"
                    placeholder="Buscar por producto o referencia…"
                    autocomplete="off"
                    class="field-input pl-9"
                >
            </div>
            <div class="min-w-0 lg:w-64">
                <label for="filtro-categoria" class="sr-only">Categoría</label>
                <select id="filtro-categoria" x-model="filtroCategoria" @change="filtrar()" class="field-input">
                    <option value="">Todas las categorías</option>
                    <template x-for="cat in categorias" :key="cat.id">
                        <option :value="String(cat.id)" x-text="cat.nombre"></option>
                    </template>
                </select>
            </div>
            <button
                type="button"
                x-show="busqueda || filtroCategoria" x-cloak
                @click="busqueda = ''; filtroCategoria = ''; filtrar()"
                class="btn btn-ghost sm:col-span-2 lg:col-span-1"
            >Limpiar filtros</button>
        </div>

        <x-admin.card :padded="false" class="lg:min-h-0 lg:flex-1" fill>
            <x-admin.data-list
                caption="Productos del catálogo"
                scroll="fill"
                body-id="tabla-productos-cuerpo"
                :columns="['Imagen', 'Producto', 'Referencia', 'Categoría', 'Especificaciones', 'Estado', ['label' => 'Acciones', 'sr' => true]]"
            >
                @forelse ($productos as $producto)
                    @include('admin.productos._fila', ['producto' => $producto])
                @empty
                    <tr id="fila-vacia">
                        <td colspan="7">
                            <x-admin.empty-state icon="productos" title="No hay productos todavía" description="Crea el primero para que aparezca en la tienda.">
                                <button type="button" @click="abrirCrear()" class="btn btn-primary btn-sm">
                                    <x-admin.icon name="mas-simple" class="h-4 w-4" />
                                    Nuevo producto
                                </button>
                            </x-admin.empty-state>
                        </td>
                    </tr>
                @endforelse
                <tr id="fila-sin-resultados" x-show="sinResultados" x-cloak>
                    <td colspan="7">
                        <x-admin.empty-state icon="buscar" title="Ningún producto coincide" description="Prueba con otro nombre, referencia o categoría.">
                            <button type="button" @click="busqueda = ''; filtroCategoria = ''; filtrar()" class="btn btn-secondary btn-sm">Limpiar filtros</button>
                        </x-admin.empty-state>
                    </td>
                </tr>
            </x-admin.data-list>
        </x-admin.card>

        {{-- Modal de producto --}}
        <div x-show="modalAbierto" x-cloak class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" @click="cerrarModal()"></div>

            <div class="relative flex max-h-[92dvh] w-full max-w-4xl flex-col overflow-hidden rounded-t-2xl bg-card shadow-2xl sm:max-h-[90vh] sm:rounded-2xl" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-producto">
                <div class="mx-auto mt-2 h-1 w-10 shrink-0 rounded-full bg-border sm:hidden" aria-hidden="true"></div>
                <div class="flex shrink-0 items-start justify-between gap-3 px-4 pb-2 pt-3 sm:px-6 sm:pt-5">
                    <div class="min-w-0">
                        <h2 id="titulo-modal-producto" class="text-lg font-semibold text-text-primary" x-text="modoEdicion ? 'Editar producto' : 'Nuevo producto'"></h2>
                        <p class="truncate text-sm text-text-caption" x-text="modoEdicion ? (form.nombre || '…') : 'Completa los datos y guarda para publicarlo.'"></p>
                    </div>
                    <button type="button" @click="cerrarModal()" class="-mr-2 flex h-11 w-11 shrink-0 items-center justify-center rounded-nav text-text-caption hover:bg-page hover:text-text-primary" aria-label="Cerrar">✕</button>
                </div>

                <div class="flex shrink-0 gap-1 overflow-x-auto whitespace-nowrap border-b border-border px-4 [scrollbar-width:none] sm:px-6" role="tablist">
                    <template x-for="tab in [['general','General'],['descripcion','Descripción'],['especificaciones','Especificaciones'],['oferta','Oferta del día']]" :key="tab[0]">
                        <button
                            type="button" role="tab" :aria-selected="tabActiva === tab[0]"
                            @click="tabActiva = tab[0]"
                            class="relative -mb-px flex min-h-[44px] shrink-0 items-center gap-1.5 border-b-2 px-3 py-2 text-sm font-medium transition-colors"
                            :class="tabActiva === tab[0] ? 'border-accent text-accent' : 'border-transparent text-text-secondary hover:text-text-primary'"
                        >
                            <span x-text="tab[1]"></span>
                            <span x-show="tabTieneError(tab[0])" class="h-1.5 w-1.5 rounded-full bg-rechazado-dot"></span>
                        </button>
                    </template>
                </div>

                <div class="relative min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-4 sm:px-6 sm:py-5 lg:min-h-[520px]" :aria-busy="cargandoEdicion ? 'true' : 'false'">

                    {{-- Mientras llegan los datos de "Editar": placeholders en vez de un formulario vacío que parece nuevo. --}}
                    <div x-show="cargandoEdicion" x-cloak class="absolute inset-0 z-10 grid grid-cols-1 gap-6 bg-card px-4 py-4 sm:px-6 sm:py-5 lg:grid-cols-[220px_minmax(0,1fr)]" aria-hidden="true">
                        <div class="skeleton mx-auto aspect-square w-full max-w-[200px] rounded-xl lg:max-w-none"></div>
                        <div class="flex flex-col gap-5">
                            <div class="skeleton h-4 w-28"></div>
                            <div class="skeleton h-11 w-full"></div>
                            <div class="skeleton h-11 w-full"></div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="skeleton h-11"></div>
                                <div class="skeleton h-11"></div>
                            </div>
                            <div class="skeleton h-4 w-36"></div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div class="skeleton h-11"></div>
                                <div class="skeleton h-11"></div>
                                <div class="skeleton h-11"></div>
                            </div>
                        </div>
                    </div>
                    <p x-show="cargandoEdicion" x-cloak class="sr-only" role="status">Cargando producto…</p>

                    {{-- Tab: General --}}
                    <div x-show="tabActiva === 'general'" class="grid grid-cols-1 gap-6 lg:grid-cols-[220px_minmax(0,1fr)]">

                        {{-- Imagen --}}
                        <div class="flex flex-col gap-2">
                            <span class="text-sm font-medium text-text-primary">
                                Imagen <span class="font-normal text-text-caption" x-text="modoEdicion ? '(opcional)' : ''"></span>
                            </span>
                            <div
                                role="button" tabindex="0" aria-label="Elegir imagen del producto"
                                @click="$refs.inputImagen.click()" @keydown.enter.prevent="$refs.inputImagen.click()"
                                @dragover.prevent="arrastrandoImagen = true" @dragleave.prevent="arrastrandoImagen = false"
                                @drop.prevent="arrastrandoImagen = false; procesarArchivo($event.dataTransfer.files[0])"
                                class="group relative mx-auto flex aspect-square w-full max-w-[200px] cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-dashed bg-page transition-colors lg:max-w-none"
                                :class="errores.imagen ? 'border-rechazado-text' : (arrastrandoImagen ? 'border-accent bg-accent/5' : (imagenPreviewUrl ? 'border-solid border-border' : 'border-border hover:border-accent/60'))"
                            >
                                <template x-if="imagenPreviewUrl">
                                    <img :src="imagenPreviewUrl" alt="Vista previa del producto" class="absolute inset-0 h-full w-full object-cover">
                                </template>
                                <div x-show="imagenPreviewUrl" class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent px-3 pb-2 pt-6 text-center text-xs font-medium text-white opacity-0 transition-opacity group-hover:opacity-100">
                                    Cambiar imagen
                                </div>
                                <div x-show="!imagenPreviewUrl" class="flex flex-col items-center gap-2 px-4 text-center">
                                    <svg class="h-10 w-10 text-text-caption" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>
                                    </svg>
                                    <p class="text-sm font-medium text-text-secondary">Arrastra o elige una imagen</p>
                                    <p class="text-xs text-text-caption">JPG, PNG o WEBP · máx. 2 MB<br>mín. 200 × 200 px</p>
                                </div>
                                <input x-ref="inputImagen" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @click.stop @change="procesarArchivo($event.target.files[0]); $event.target.value = ''">
                            </div>
                            <div class="flex items-center justify-center gap-1 lg:justify-start" x-show="imagenPreviewUrl">
                                <button type="button" @click="$refs.inputImagen.click()" class="inline-flex min-h-[44px] items-center rounded-nav px-2 text-xs font-medium text-accent hover:bg-page sm:min-h-[32px]">Cambiar</button>
                                <span class="text-text-disabled" aria-hidden="true">·</span>
                                <button
                                    type="button" @click="quitarImagen()"
                                    class="inline-flex min-h-[44px] items-center rounded-nav px-2 text-xs font-medium text-rechazado-text hover:bg-page sm:min-h-[32px]"
                                    x-text="imagenArchivo ? 'Deshacer' : 'Quitar imagen'"
                                ></button>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-page" x-show="guardando && subiendoProgreso > 0">
                                <div class="h-full bg-accent transition-all" :style="`width: ${subiendoProgreso}%`"></div>
                            </div>
                            <p class="text-xs text-rechazado-text" x-show="errores.imagen" x-text="errores.imagen?.[0]"></p>
                        </div>

                        <div class="flex min-w-0 flex-col gap-6">

                            {{-- Identificación --}}
                            <section class="flex flex-col gap-4">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-text-caption">Identificación</h3>
                                <div class="min-w-0">
                                    <label for="producto-nombre" class="mb-1 block text-sm font-medium text-text-primary">Nombre del producto</label>
                                    <input
                                        id="producto-nombre" type="text" x-model="form.nombre" @input="onNombreInput()"
                                        maxlength="150" required placeholder="Ej. Monitor LG 27&quot; 4K"
                                        class="h-11 w-full rounded-nav border bg-card px-3 text-base focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 sm:h-10 sm:text-sm"
                                        :class="errores.nombre ? 'border-rechazado-text' : 'border-border'"
                                    >
                                    <p class="mt-1 text-xs text-rechazado-text" x-show="errores.nombre" x-text="errores.nombre?.[0]"></p>
                                </div>
                                <div class="min-w-0">
                                    <label for="producto-slug" class="mb-1 block text-sm font-medium text-text-primary">Slug <span class="font-normal text-text-caption">(URL, editable)</span></label>
                                    <input
                                        id="producto-slug" type="text" x-model="form.slug" @input="onSlugInput()"
                                        maxlength="170" autocapitalize="none" spellcheck="false" placeholder="se-genera-desde-el-nombre"
                                        class="h-11 w-full rounded-nav border bg-card px-3 font-mono text-base focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 sm:h-10 sm:text-sm"
                                        :class="errores.slug ? 'border-rechazado-text' : 'border-border'"
                                    >
                                    <p class="mt-1 text-xs text-rechazado-text" x-show="errores.slug" x-text="errores.slug?.[0]"></p>
                                    <p class="mt-1 text-xs text-text-caption" x-show="!errores.slug">Si ya existe, se le agrega un sufijo (-2, -3…).</p>
                                </div>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="min-w-0">
                                        <label for="producto-referencia" class="mb-1 block text-sm font-medium text-text-primary">Referencia</label>
                                        <input
                                            id="producto-referencia" type="text" x-model="form.referencia" @input="verificarReferencia()"
                                            maxlength="50" required autocapitalize="characters" spellcheck="false" placeholder="Ej. MON-LG-27"
                                            class="h-11 w-full rounded-nav border bg-card px-3 font-mono text-base uppercase placeholder:normal-case focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 sm:h-10 sm:text-sm"
                                            :class="errores.referencia || ['ocupada', 'eliminada'].includes(referenciaEstado) ? 'border-rechazado-text' : (referenciaEstado === 'disponible' ? 'border-pagado-dot' : 'border-border')"
                                        >
                                        <p class="mt-1 text-xs text-rechazado-text" x-show="errores.referencia" x-text="errores.referencia?.[0]"></p>
                                        <div x-show="!errores.referencia && referenciaEstado" class="mt-1 text-xs" aria-live="polite">
                                            <p x-show="referenciaEstado === 'verificando'" class="text-text-caption">Verificando…</p>
                                            <p x-show="referenciaEstado === 'disponible'" class="text-pagado-text">✓ Referencia disponible.</p>
                                            <p x-show="referenciaEstado === 'ocupada'" class="text-rechazado-text">
                                                Ya existe un producto con esta referencia<span x-show="referenciaProducto?.nombre"> («<span x-text="referenciaProducto?.nombre"></span>»)</span>.
                                            </p>
                                            <div x-show="referenciaEstado === 'eliminada'" class="flex flex-col gap-2 rounded-nav bg-rechazado-bg p-2 text-rechazado-text">
                                                <p class="min-w-0 break-words">Es de «<span x-text="referenciaProducto?.nombre"></span>», que está en la papelera.</p>
                                                <button
                                                    type="button" @click="restaurarProductoEliminado()" :disabled="restaurando"
                                                    class="inline-flex min-h-[44px] items-center justify-center rounded-nav border border-current px-3 text-xs font-medium hover:bg-card disabled:opacity-60 sm:min-h-[36px]"
                                                    x-text="restaurando ? 'Restaurando…' : 'Restaurar ese producto'"
                                                ></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <label for="producto-categoria" class="mb-1 block text-sm font-medium text-text-primary">Categoría</label>
                                        <div class="flex gap-2">
                                            <select
                                                id="producto-categoria" x-model="form.categoria_id" @change="limpiarError('categoria_id')"
                                                class="h-11 w-full min-w-0 flex-1 rounded-nav border bg-card px-3 text-base focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 sm:h-10 sm:text-sm"
                                                :class="errores.categoria_id ? 'border-rechazado-text' : 'border-border'"
                                            >
                                                <option value="">Selecciona…</option>
                                                <template x-for="cat in categorias" :key="cat.id">
                                                    <option :value="cat.id" x-text="cat.nombre"></option>
                                                </template>
                                            </select>
                                            <button type="button" @click="abrirCategoriaModal()" title="Crear categoría" aria-label="Crear categoría" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-nav border border-border bg-card text-lg leading-none text-text-secondary hover:border-accent hover:text-accent sm:h-10 sm:w-10">+</button>
                                        </div>
                                        <p class="mt-1 text-xs text-rechazado-text" x-show="errores.categoria_id" x-text="errores.categoria_id?.[0]"></p>
                                    </div>
                                </div>
                            </section>

                            {{-- Precio e inventario --}}
                            <section class="flex flex-col gap-4 border-t border-border pt-5">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-text-caption">Precio e inventario</h3>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div class="min-w-0">
                                        <label for="producto-precio" class="mb-1 block text-sm font-medium text-text-primary">Precio</label>
                                        <div class="relative">
                                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-text-caption">S/</span>
                                            <input
                                                id="producto-precio" type="number" x-model="form.precio" @input="limpiarError('precio')"
                                                step="0.01" min="0.01" max="99999.99" inputmode="decimal" required placeholder="0.00"
                                                class="h-11 w-full rounded-nav border bg-card pl-9 pr-3 text-base tabular-nums focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 sm:h-10 sm:text-sm"
                                                :class="errores.precio ? 'border-rechazado-text' : 'border-border'"
                                            >
                                        </div>
                                        <p class="mt-1 text-xs text-rechazado-text" x-show="errores.precio" x-text="errores.precio?.[0]"></p>
                                    </div>
                                    <div class="min-w-0">
                                        <label for="producto-stock" class="mb-1 block text-sm font-medium text-text-primary">Stock</label>
                                        <input
                                            id="producto-stock" type="number" x-model="form.stock" @input="limpiarError('stock')"
                                            step="1" min="0" inputmode="numeric"
                                            class="h-11 w-full rounded-nav border bg-card px-3 text-base tabular-nums focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 sm:h-10 sm:text-sm"
                                            :class="errores.stock ? 'border-rechazado-text' : 'border-border'"
                                        >
                                        <p class="mt-1 text-xs text-rechazado-text" x-show="errores.stock" x-text="errores.stock?.[0]"></p>
                                    </div>
                                    <div class="min-w-0">
                                        <label for="producto-orden" class="mb-1 block text-sm font-medium text-text-primary">Orden <span class="font-normal text-text-caption">(en el catálogo)</span></label>
                                        <input
                                            id="producto-orden" type="number" x-model="form.orden" @input="limpiarError('orden')"
                                            step="1" min="0" inputmode="numeric"
                                            class="h-11 w-full rounded-nav border bg-card px-3 text-base tabular-nums focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 sm:h-10 sm:text-sm"
                                            :class="errores.orden ? 'border-rechazado-text' : 'border-border'"
                                        >
                                        <p class="mt-1 text-xs text-rechazado-text" x-show="errores.orden" x-text="errores.orden?.[0]"></p>
                                    </div>
                                </div>
                            </section>

                            {{-- Publicación --}}
                            <section class="flex flex-col gap-3 border-t border-border pt-5">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-text-caption">Publicación</h3>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors" :class="form.activo ? 'border-accent/40 bg-accent/5' : 'border-border hover:bg-page'">
                                        <input type="checkbox" x-model="form.activo" class="peer sr-only">
                                        <span class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 rounded-full transition-colors peer-focus-visible:ring-2 peer-focus-visible:ring-accent/40" :class="form.activo ? 'bg-accent' : 'bg-border'" aria-hidden="true">
                                            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform" :class="form.activo ? 'translate-x-5' : ''"></span>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-text-primary">Visible en la tienda</span>
                                            <span class="block text-xs text-text-caption">Aparece en /productos y se puede comprar.</span>
                                        </span>
                                    </label>
                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors" :class="form.destacado ? 'border-accent/40 bg-accent/5' : 'border-border hover:bg-page'">
                                        <input type="checkbox" x-model="form.destacado" class="peer sr-only">
                                        <span class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 rounded-full transition-colors peer-focus-visible:ring-2 peer-focus-visible:ring-accent/40" :class="form.destacado ? 'bg-accent' : 'bg-border'" aria-hidden="true">
                                            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform" :class="form.destacado ? 'translate-x-5' : ''"></span>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-text-primary">Destacado</span>
                                            <span class="block text-xs text-text-caption">Marca interna; el catálogo aún no lo muestra distinto.</span>
                                        </span>
                                    </label>
                                </div>
                            </section>
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
                                class="flex items-start gap-2 rounded-nav border border-border bg-page p-2 sm:items-center"
                            >
                                <span class="flex h-11 cursor-move items-center px-1 text-text-caption sm:h-9" aria-hidden="true">⠿</span>
                                <div class="grid min-w-0 flex-1 grid-cols-1 gap-2 sm:grid-cols-[1fr_2fr]">
                                    <input type="text" x-model="fila.clave" @input="limpiarError('especificaciones')" placeholder="Clave" maxlength="100" class="h-11 w-full min-w-0 rounded-nav border bg-card px-2 text-base sm:h-9 sm:text-sm" :class="errores.especificaciones && !String(fila.clave || '').trim() && String(fila.valor || '').trim() ? 'border-rechazado-text' : 'border-border'">
                                    <input type="text" x-model="fila.valor" @input="limpiarError('especificaciones')" placeholder="Valor" maxlength="500" class="h-11 w-full min-w-0 rounded-nav border border-border bg-card px-2 text-base sm:h-9 sm:text-sm">
                                </div>
                                <button type="button" @click="especificaciones.splice(i, 1)" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-nav text-text-caption hover:bg-card sm:h-9 sm:w-9" aria-label="Eliminar fila">✕</button>
                            </div>
                        </template>

                        <button type="button" @click="especificaciones.push({ clave: '', valor: '' })" class="inline-flex min-h-[44px] w-full items-center justify-center rounded-nav border border-border px-3 text-sm font-medium text-text-secondary hover:bg-page sm:h-9 sm:min-h-0 sm:w-fit">
                            + Agregar fila
                        </button>
                        <ul class="flex flex-col gap-0.5 text-xs text-rechazado-text" x-show="errores.especificaciones">
                            <template x-for="mensaje in (errores.especificaciones || [])" :key="mensaje">
                                <li x-text="mensaje"></li>
                            </template>
                        </ul>
                    </div>

                    {{-- Tab: Oferta del día --}}
                    <div x-show="tabActiva === 'oferta'" class="flex flex-col gap-6">

                        <section class="flex flex-col gap-3">
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors" :class="errores.oferta ? 'border-rechazado-text' : (form.oferta ? 'border-accent/40 bg-accent/5' : 'border-border hover:bg-page')">
                                <input type="checkbox" x-model="form.oferta" @change="limpiarError('oferta')" class="peer sr-only">
                                <span class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 rounded-full transition-colors peer-focus-visible:ring-2 peer-focus-visible:ring-accent/40" :class="form.oferta ? 'bg-accent' : 'bg-border'" aria-hidden="true">
                                    <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform" :class="form.oferta ? 'translate-x-5' : ''"></span>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-text-primary">Publicar como oferta del día</span>
                                    <span class="block text-xs text-text-caption">Aparece arriba en /productos con un contador de 24 horas. Si otro producto era la oferta, deja de serlo.</span>
                                </span>
                            </label>
                            <p class="text-xs text-rechazado-text" x-show="errores.oferta" x-text="errores.oferta?.[0]"></p>

                            {{-- Estado de la ventana de 24 h (solo si este producto ya es la oferta vigente). --}}
                            <div x-show="ofertaTerminaEn && form.oferta" class="flex flex-col gap-2 rounded-xl bg-verificacion-bg p-3 text-sm text-verificacion-text sm:flex-row sm:items-center sm:justify-between">
                                <p class="min-w-0">Vigente hasta el <strong class="tabular-nums" x-text="formatearVencimiento(ofertaTerminaEn)"></strong>.</p>
                                <label class="flex min-h-[44px] cursor-pointer items-center gap-2 sm:min-h-0">
                                    <input type="checkbox" x-model="form.oferta_reiniciar" class="h-4 w-4 rounded border-border accent-accent">
                                    <span>Reiniciar 24 h al guardar</span>
                                </label>
                            </div>
                            <p x-show="form.oferta && !ofertaTerminaEn" class="text-xs text-text-caption">Al guardar empiezan a correr las 24 horas.</p>
                            <p x-show="!form.oferta && ofertaTerminaEn" class="text-xs text-rechazado-text">Al guardar, la oferta deja de mostrarse en /productos. Los textos se conservan.</p>
                        </section>

                        <section class="flex flex-col gap-4 border-t border-border pt-5">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-text-caption">Textos de la portada</h3>
                            <div class="min-w-0">
                                <label for="oferta-titular" class="mb-1 block text-sm font-medium text-text-primary">Titular</label>
                                <input
                                    id="oferta-titular" type="text" x-model="form.oferta_titular" @input="limpiarError('oferta_titular')"
                                    maxlength="120" placeholder="Ej. La potencia que buscas. Hoy es el momento."
                                    class="h-11 w-full rounded-nav border bg-card px-3 text-base focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 sm:h-10 sm:text-sm"
                                    :class="errores.oferta_titular ? 'border-rechazado-text' : 'border-border'"
                                >
                                <p class="mt-1 text-xs text-text-caption">Va debajo del nombre del producto. El título grande de la portada es fijo: «¡Oferta, aprovecha ahora!».</p>
                                <p class="mt-1 text-xs text-rechazado-text" x-show="errores.oferta_titular" x-text="errores.oferta_titular?.[0]"></p>
                            </div>
                            <div class="min-w-0">
                                <label for="oferta-bajada" class="mb-1 block text-sm font-medium text-text-primary">Bajada <span class="font-normal text-text-caption">(opcional)</span></label>
                                <textarea
                                    id="oferta-bajada" x-model="form.oferta_bajada" @input="limpiarError('oferta_bajada')"
                                    maxlength="300" rows="3" placeholder="Ej. Descubre el ROG Zephyrus G16 en nuestra oferta especial por tiempo limitado."
                                    class="w-full rounded-nav border bg-card px-3 py-2 text-base focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 sm:text-sm"
                                    :class="errores.oferta_bajada ? 'border-rechazado-text' : 'border-border'"
                                ></textarea>
                                <p class="mt-1 text-xs text-rechazado-text" x-show="errores.oferta_bajada" x-text="errores.oferta_bajada?.[0]"></p>
                            </div>
                            <div class="min-w-0 sm:max-w-[16rem]">
                                <label for="producto-precio-comparacion" class="mb-1 block text-sm font-medium text-text-primary">Precio regular <span class="font-normal text-text-caption">(opcional)</span></label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-text-caption">S/</span>
                                    <input
                                        id="producto-precio-comparacion" type="number" x-model="form.precio_comparacion" @input="limpiarError('precio_comparacion')"
                                        step="0.01" min="0.01" max="99999.99" inputmode="decimal" placeholder="0.00"
                                        class="h-11 w-full rounded-nav border bg-card pl-9 pr-3 text-base tabular-nums focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20 sm:h-10 sm:text-sm"
                                        :class="errores.precio_comparacion ? 'border-rechazado-text' : 'border-border'"
                                    >
                                </div>
                                <p class="mt-1 text-xs text-rechazado-text" x-show="errores.precio_comparacion" x-text="errores.precio_comparacion?.[0]"></p>
                                <p class="mt-1 text-xs text-text-caption" x-show="!errores.precio_comparacion">Se muestra tachado junto al precio de venta.</p>
                            </div>
                            <p class="text-xs text-text-caption">La imagen, el precio de venta y la descripción salen de la pestaña General y Descripción; las 4 primeras filas de Especificaciones se muestran como fichas.</p>
                        </section>

                        <section class="flex flex-col gap-3 border-t border-border pt-5">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-text-caption">Beneficios <span class="font-normal normal-case tracking-normal">(opcional, hasta 3)</span></h3>
                            <template x-for="(beneficio, i) in beneficios" :key="i">
                                <div class="grid grid-cols-1 gap-2 rounded-nav border border-border bg-page p-3 sm:grid-cols-[2.5rem_minmax(0,1fr)_minmax(0,2fr)] sm:items-center">
                                    <span class="font-mono text-xs font-semibold text-accent" x-text="`0${i + 1} /`"></span>
                                    <input type="text" x-model="beneficio.titulo" @input="limpiarError('oferta_beneficios')" placeholder="Título, ej. Imagen que impresiona" maxlength="60" :aria-label="`Título del beneficio ${i + 1}`" class="h-11 w-full min-w-0 rounded-nav border bg-card px-2 text-base sm:h-9 sm:text-sm" :class="errores.oferta_beneficios && !String(beneficio.titulo || '').trim() && String(beneficio.texto || '').trim() ? 'border-rechazado-text' : 'border-border'">
                                    <input type="text" x-model="beneficio.texto" @input="limpiarError('oferta_beneficios')" placeholder="Texto corto" maxlength="200" :aria-label="`Texto del beneficio ${i + 1}`" class="h-11 w-full min-w-0 rounded-nav border border-border bg-card px-2 text-base sm:h-9 sm:text-sm">
                                </div>
                            </template>
                            <ul class="flex flex-col gap-0.5 text-xs text-rechazado-text" x-show="errores.oferta_beneficios">
                                <template x-for="mensaje in (errores.oferta_beneficios || [])" :key="mensaje">
                                    <li x-text="mensaje"></li>
                                </template>
                            </ul>
                        </section>
                    </div>

                </div>

                <div class="flex shrink-0 flex-col gap-2 border-t border-border bg-card px-4 py-3 sm:flex-row sm:items-center sm:gap-3 sm:px-6 sm:py-4">
                    <p class="text-center text-xs font-medium text-rechazado-text sm:mr-auto sm:text-left" x-show="Object.keys(errores).length" aria-live="polite"
                       x-text="Object.keys(errores).length === 1 ? 'Revisa 1 campo marcado en rojo.' : `Revisa ${Object.keys(errores).length} campos marcados en rojo.`"></p>
                    <div class="flex flex-col-reverse gap-2 sm:ml-auto sm:flex-row sm:gap-3">
                        <button type="button" @click="cerrarModal()" class="btn btn-secondary w-full sm:w-auto">Cancelar</button>
                        <button type="button" @click="guardar()" :disabled="guardando || cargandoEdicion" :aria-busy="guardando ? 'true' : 'false'" class="btn btn-primary w-full sm:w-auto sm:min-w-[150px]">
                            <span x-text="guardando ? 'Guardando…' : (modoEdicion ? 'Guardar cambios' : 'Crear producto')"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal anidado: crear categoría rápida. El fondo va casi transparente
             porque se abre sobre el modal de producto, que ya oscureció la
             página con su propio bg-black/40 (sumar otro bg-black/50 dejaba
             todo casi negro). --}}
        <div x-show="categoriaModalAbierto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/10" @click="categoriaModalAbierto = false"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-card p-5 shadow-pop" role="dialog" aria-modal="true" aria-labelledby="titulo-nueva-categoria">
                <h3 id="titulo-nueva-categoria" class="mb-3 text-sm font-semibold text-text-primary">Nueva categoría</h3>
                <label for="nueva-categoria" class="sr-only">Nombre de la categoría</label>
                <input
                    id="nueva-categoria" type="text" x-model="categoriaNombre" @keydown.enter="crearCategoria()"
                    placeholder="Nombre de la categoría" maxlength="100"
                    class="field-input"
                    :aria-invalid="categoriaError ? 'true' : 'false'"
                >
                <p class="mt-1 text-xs text-rechazado-text" x-show="categoriaError" x-text="categoriaError"></p>
                <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3">
                    <button type="button" @click="categoriaModalAbierto = false" class="btn btn-secondary">Cancelar</button>
                    <button type="button" @click="crearCategoria()" :disabled="categoriaGuardando" :aria-busy="categoriaGuardando ? 'true' : 'false'" class="btn btn-primary">Crear</button>
                </div>
            </div>
        </div>

        {{-- Confirmación (eliminar / activar / desactivar) con resumen del producto. --}}
        <div x-show="confirmacion.abierta" x-cloak class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/40" @click="confirmacion.abierta = false"></div>
            <div class="relative w-full rounded-t-2xl bg-card p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] shadow-pop sm:max-w-sm sm:rounded-2xl sm:pb-5">
                <h3 class="mb-3 text-base font-semibold text-text-primary" x-text="confirmacion.titulo"></h3>
                <template x-if="confirmacion.resumen">
                    <div class="mb-3 flex items-center gap-3 rounded-nav border border-border bg-page p-2">
                        <img :src="confirmacion.resumen.imagen" alt="" class="h-12 w-12 shrink-0 rounded-nav object-cover">
                        <div class="min-w-0 text-sm">
                            <p class="truncate font-medium text-text-primary" x-text="confirmacion.resumen.nombre"></p>
                            <p class="truncate text-xs text-text-caption">
                                <span class="font-mono" x-text="confirmacion.resumen.referencia"></span> · <span x-text="confirmacion.resumen.categoria"></span>
                            </p>
                        </div>
                    </div>
                </template>
                <p class="mb-4 text-sm text-text-secondary" x-text="confirmacion.mensaje"></p>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3">
                    <button type="button" @click="confirmacion.abierta = false" class="btn btn-secondary">Cancelar</button>
                    <button
                        type="button" @click="confirmarAccion()" :disabled="accionEnCurso" :aria-busy="accionEnCurso ? 'true' : 'false'"
                        class="btn"
                        :class="confirmacion.peligroso ? 'btn-danger' : 'btn-primary'"
                        x-text="confirmacion.textoBoton"
                    ></button>
                </div>
            </div>
        </div>

        {{-- Vista rápida: datos del producto sin abrir el editor. --}}
        <div x-show="vistaRapida.abierta" x-cloak class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/40" @click="vistaRapida.abierta = false"></div>
            <div class="relative flex max-h-[92dvh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-card shadow-xl sm:max-h-[90vh] sm:rounded-2xl">
                <div class="flex shrink-0 items-center justify-between border-b border-border px-4 py-3 sm:px-6">
                    <h2 class="min-w-0 truncate text-lg font-semibold text-text-primary" x-text="vistaRapida.datos?.nombre || 'Producto'"></h2>
                    <button type="button" @click="vistaRapida.abierta = false" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-nav text-text-caption hover:bg-page" aria-label="Cerrar">✕</button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-4 sm:px-6">
                    <div x-show="vistaRapida.cargando" class="grid grid-cols-1 gap-4 sm:grid-cols-[180px_1fr]" aria-hidden="true">
                        <div class="skeleton aspect-square w-full sm:w-[180px]"></div>
                        <div class="flex flex-col gap-3">
                            <div class="skeleton h-4 w-3/4"></div>
                            <div class="skeleton h-4 w-1/2"></div>
                            <div class="skeleton h-4 w-2/3"></div>
                            <div class="skeleton h-4 w-1/3"></div>
                            <div class="skeleton h-4 w-1/2"></div>
                        </div>
                    </div>
                    <p x-show="vistaRapida.cargando" class="sr-only" role="status">Cargando producto…</p>
                    <template x-if="!vistaRapida.cargando && vistaRapida.datos">
                        <div class="flex flex-col gap-4">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-[180px_1fr]">
                                <img :src="vistaRapida.datos.imagen_url" alt="" class="aspect-square w-full rounded-nav border border-border object-cover sm:w-[180px]">
                                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                    <dt class="text-text-caption">Referencia</dt><dd class="font-mono text-text-primary" x-text="vistaRapida.datos.referencia"></dd>
                                    <dt class="text-text-caption">Categoría</dt><dd class="text-text-primary" x-text="vistaRapida.datos.categoria || 'Sin categoría'"></dd>
                                    <dt class="text-text-caption">Precio</dt><dd class="font-medium text-text-primary" x-text="'S/ ' + vistaRapida.datos.precio"></dd>
                                    <dt class="text-text-caption">Stock</dt><dd class="text-text-primary" x-text="vistaRapida.datos.stock"></dd>
                                    <dt class="text-text-caption">Estado</dt>
                                    <dd class="flex flex-wrap gap-1">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium leading-5" :class="vistaRapida.datos.activo ? 'bg-pagado-bg text-pagado-text' : 'bg-anulado-bg text-anulado-text'">
                                            <span class="h-1.5 w-1.5 rounded-full" :class="vistaRapida.datos.activo ? 'bg-pagado-dot' : 'bg-anulado-dot'" aria-hidden="true"></span>
                                            <span x-text="vistaRapida.datos.activo ? 'Activo' : 'Inactivo'"></span>
                                        </span>
                                        <span x-show="vistaRapida.datos.destacado" class="rounded-full bg-verificacion-bg px-2.5 py-0.5 text-xs font-medium leading-5 text-verificacion-text">Destacado</span>
                                        <span x-show="vistaRapida.datos.oferta" class="rounded-full bg-verificacion-bg px-2.5 py-0.5 text-xs font-medium leading-5 text-verificacion-text" x-text="`Oferta · vence ${formatearVencimiento(vistaRapida.datos.oferta_termina_en)}`"></span>
                                    </dd>
                                    <dt class="text-text-caption">Slug</dt><dd class="break-all font-mono text-xs text-text-secondary" x-text="vistaRapida.datos.slug"></dd>
                                </dl>
                            </div>
                            <div x-show="(vistaRapida.datos.especificaciones || []).length">
                                <h3 class="mb-2 text-sm font-semibold text-text-primary">Especificaciones</h3>
                                <dl class="divide-y divide-border rounded-nav border border-border text-sm">
                                    <template x-for="(fila, i) in vistaRapida.datos.especificaciones" :key="i">
                                        <div class="grid grid-cols-1 gap-1 px-3 py-2 sm:grid-cols-[1fr_2fr]">
                                            <dt class="text-text-caption" x-text="fila.clave"></dt>
                                            <dd class="break-words text-text-primary" x-text="fila.valor"></dd>
                                        </div>
                                    </template>
                                </dl>
                            </div>
                            <div>
                                <h3 class="mb-2 text-sm font-semibold text-text-primary">Descripción</h3>
                                {{-- descripcion viene saneada por el mutator de Producto (Purifier). --}}
                                <div class="prose-sm break-words text-sm text-text-secondary [&_ol]:list-decimal [&_ol]:pl-5 [&_ul]:list-disc [&_ul]:pl-5" x-html="vistaRapida.datos.descripcion"></div>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-border px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:flex-row sm:justify-end sm:px-6 sm:pb-3">
                    <button type="button" @click="vistaRapida.abierta = false" class="btn btn-secondary">Cerrar</button>
                    <button type="button" @click="editarDesdeVistaRapida()" :disabled="!vistaRapida.datos" class="btn btn-primary">Editar</button>
                </div>
            </div>
        </div>

        {{-- Aviso de cambios sin guardar al cerrar el modal de producto. --}}
        <div x-show="descarte.abierto" x-cloak class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4" role="alertdialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/10" @click="descarte.abierto = false"></div>
            <div class="relative w-full rounded-t-2xl bg-card p-5 shadow-xl sm:max-w-sm sm:rounded-2xl">
                <h3 class="mb-2 text-sm font-semibold text-text-primary">¿Descartar los cambios?</h3>
                <p class="mb-4 text-sm text-text-secondary">Hay cambios sin guardar en este producto. Si cierras ahora, se pierden.</p>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3">
                    <button type="button" @click="descarte.abierto = false" class="btn btn-secondary">Seguir editando</button>
                    <button type="button" @click="descarte.abierto = false; cerrarModal(true)" class="btn btn-danger">Descartar cambios</button>
                </div>
            </div>
        </div>

        {{-- Lightbox de imagen --}}
        <div x-show="lightboxUrl" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-6" @click.self="lightboxUrl = null">
            <img :src="lightboxUrl" alt="" class="max-h-full max-w-full rounded-nav">
        </div>

        {{-- Toast --}}
        <div x-show="toastMensaje" x-cloak x-transition role="status" aria-live="polite" class="fixed inset-x-4 bottom-[calc(var(--spacing-bottomnav)+env(safe-area-inset-bottom)+0.75rem)] z-[75] rounded-nav px-4 py-3 text-sm font-medium text-white shadow-pop md:inset-x-auto md:bottom-6 md:right-6" :class="toastError ? 'bg-danger-strong' : 'bg-sidebar-active'" x-text="toastMensaje"></div>
    </div>

    @push('scripts')
        <script nonce="{{ $cspNonce }}">
            function productosApp(categoriasIniciales, totalEliminadosInicial) {
                const rutaProductos = @js(route('admin.productos.index'));
                const rutaVerificarReferencia = @js(route('admin.productos.verificar-referencia'));
                const rutaCategorias = @js(route('admin.categorias.store'));
                const camposPorTab = {
                    general: ['categoria_id', 'referencia', 'nombre', 'slug', 'imagen', 'precio', 'stock', 'orden'],
                    descripcion: ['descripcion'],
                    especificaciones: ['especificaciones'],
                    oferta: ['oferta', 'oferta_titular', 'oferta_bajada', 'oferta_beneficios', 'precio_comparacion'],
                };
                const MAX_BENEFICIOS = 3;

                // Funcion plana (no metodo del objeto): "form: this.formVacio()"
                // de abajo se evalua MIENTRAS el objeto x-data todavia se esta
                // construyendo, antes de que exista como tal - ahi "this" no es
                // el componente (es undefined/window), asi que this.formVacio()
                // reventaba con un TypeError en cuanto Alpine evaluaba
                // x-data="productosApp(...)", dejando TODA la pagina sin datos
                // (de ahi los "X is not defined" en cualquier parte, no solo en
                // el formulario).
                function formVacio() {
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
                        precio_comparacion: '',
                        oferta: false,
                        oferta_reiniciar: false,
                        oferta_titular: '',
                        oferta_bajada: '',
                    };
                }

                // Siempre 3 casillas (las tarjetas 01/02/03 de la oferta); las
                // vacías no se guardan (ver ProductosController::beneficios).
                function beneficiosVacios(existentes = []) {
                    return Array.from({ length: MAX_BENEFICIOS }, (_, i) => ({
                        titulo: existentes[i]?.titulo ?? '',
                        texto: existentes[i]?.texto ?? '',
                    }));
                }

                return {
                    categorias: categoriasIniciales,
                    busqueda: '',
                    filtroCategoria: '',
                    sinResultados: false,
                    cargandoEdicion: false,

                    modalAbierto: false,
                    modoEdicion: false,
                    productoId: null,
                    tabActiva: 'general',
                    guardando: false,
                    subiendoProgreso: 0,
                    errores: {},
                    slugTocado: false,

                    form: formVacio(),
                    especificaciones: [],
                    arrastrando: null,
                    beneficios: beneficiosVacios(),
                    ofertaTerminaEn: null,

                    imagenArchivo: null,
                    arrastrandoImagen: false,
                    imagenPreviewUrl: null,
                    imagenExistenteUrl: null,
                    eliminarImagenExistente: false,

                    referenciaEstado: null,
                    referenciaProducto: null,
                    referenciaCheckTimer: null,
                    referenciaAbort: null,
                    restaurando: false,

                    categoriaModalAbierto: false,
                    categoriaNombre: '',
                    categoriaGuardando: false,
                    categoriaError: null,

                    lightboxUrl: null,
                    toastMensaje: null,
                    toastError: false,

                    confirmacion: {
                        abierta: false,
                        titulo: '',
                        mensaje: '',
                        textoBoton: '',
                        peligroso: false,
                        resumen: null,
                        accion: null,
                    },
                    accionEnCurso: false,
                    totalEliminados: totalEliminadosInicial,

                    vistaRapida: { abierta: false, cargando: false, datos: null },
                    descarte: { abierto: false },
                    estadoInicial: null,

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

                        window.addEventListener('beforeunload', (evento) => {
                            if (this.hayCambiosSinGuardar()) {
                                evento.preventDefault();
                                evento.returnValue = '';
                            }
                        });
                    },

                    csrfToken() {
                        return document.querySelector('meta[name="csrf-token"]').content;
                    },

                    filtrar() {
                        const q = this.busqueda.trim().toLowerCase();
                        const cat = this.filtroCategoria;
                        const filas = document.querySelectorAll('#tabla-productos-cuerpo tr[data-id]');
                        let visibles = 0;
                        filas.forEach((fila) => {
                            const coincideTexto = !q || fila.dataset.nombre.includes(q) || fila.dataset.referencia.includes(q);
                            const coincideCategoria = !cat || fila.dataset.categoriaId === cat;
                            const visible = coincideTexto && coincideCategoria;
                            fila.style.display = visible ? '' : 'none';
                            visibles += visible ? 1 : 0;
                        });
                        this.sinResultados = filas.length > 0 && visibles === 0;
                    },

                    abrirCrear() {
                        this.modoEdicion = false;
                        this.productoId = null;
                        this.errores = {};
                        this.tabActiva = 'general';
                        this.slugTocado = false;
                        this.form = formVacio();
                        this.especificaciones = [];
                        this.beneficios = beneficiosVacios();
                        this.ofertaTerminaEn = null;
                        this.imagenArchivo = null;
                        this.imagenPreviewUrl = null;
                        this.imagenExistenteUrl = null;
                        this.eliminarImagenExistente = false;
                        this.reiniciarReferencia();
                        if (this.quill) {
                            this.quill.setText('');
                        }
                        this.modalAbierto = true;
                        this.tomarFotoInicial();
                    },

                    async abrirEditar(id) {
                        this.errores = {};
                        this.tabActiva = 'general';
                        this.modoEdicion = true;
                        this.productoId = id;
                        this.slugTocado = true;
                        this.reiniciarReferencia();
                        this.eliminarImagenExistente = false;
                        this.estadoInicial = null;
                        this.cargandoEdicion = true;
                        this.modalAbierto = true;

                        let datos;
                        try {
                            const respuesta = await fetch(`${rutaProductos}/${id}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                            if (!respuesta.ok) {
                                throw new Error(`HTTP ${respuesta.status}`);
                            }
                            datos = await respuesta.json();
                        } catch (e) {
                            this.cerrarModal(true);
                            this.mostrarToast('No se pudo cargar el producto.', true);
                            return;
                        } finally {
                            this.cargandoEdicion = false;
                        }

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
                            precio_comparacion: datos.precio_comparacion ?? '',
                            oferta: datos.oferta,
                            oferta_reiniciar: false,
                            oferta_titular: datos.oferta_titular ?? '',
                            oferta_bajada: datos.oferta_bajada ?? '',
                        };
                        this.especificaciones = (datos.especificaciones || []).map((f) => ({ clave: f.clave, valor: f.valor }));
                        this.beneficios = beneficiosVacios(datos.oferta_beneficios || []);
                        this.ofertaTerminaEn = datos.oferta_termina_en;
                        this.imagenArchivo = null;
                        this.imagenPreviewUrl = datos.tiene_imagen ? datos.imagen_url : null;
                        this.imagenExistenteUrl = this.imagenPreviewUrl;

                        if (this.quill) {
                            this.quill.setText('');
                            this.quill.clipboard.dangerouslyPasteHTML(datos.descripcion || '');
                        }
                        this.tomarFotoInicial();
                    },

                    cerrarModal(forzar = false) {
                        if (!forzar && this.hayCambiosSinGuardar()) {
                            this.descarte.abierto = true;
                            return;
                        }
                        this.reiniciarReferencia();
                        this.estadoInicial = null;
                        this.modalAbierto = false;
                    },

                    limpiarError(campo) {
                        if (this.errores[campo]) {
                            const { [campo]: _, ...resto } = this.errores;
                            this.errores = resto;
                        }
                    },

                    onNombreInput() {
                        this.limpiarError('nombre');
                        if (!this.slugTocado) {
                            this.form.slug = this.slugify(this.form.nombre);
                            this.limpiarError('slug');
                        }
                    },

                    onSlugInput() {
                        this.slugTocado = true;
                        this.limpiarError('slug');
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
                        const url = URL.createObjectURL(archivo);
                        const img = new Image();
                        img.onload = () => {
                            if (img.naturalWidth < 200 || img.naturalHeight < 200) {
                                URL.revokeObjectURL(url);
                                this.errores = { ...this.errores, imagen: ['La imagen debe medir al menos 200 × 200 px.'] };
                                return;
                            }
                            this.limpiarError('imagen');
                            this.imagenArchivo = archivo;
                            this.imagenPreviewUrl = url;
                            this.eliminarImagenExistente = false;
                        };
                        img.onerror = () => {
                            URL.revokeObjectURL(url);
                            this.errores = { ...this.errores, imagen: ['No se pudo leer la imagen. Prueba con otro archivo.'] };
                        };
                        img.src = url;
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

                    reiniciarReferencia() {
                        clearTimeout(this.referenciaCheckTimer);
                        this.referenciaAbort?.abort();
                        this.referenciaAbort = null;
                        this.referenciaEstado = null;
                        this.referenciaProducto = null;
                    },

                    /**
                     * Debounce de 400 ms + AbortController: cada tecla
                     * cancela el chequeo anterior (temporizador y fetch en
                     * vuelo), así una respuesta vieja nunca pisa el estado
                     * de lo que el admin escribió después.
                     */
                    verificarReferencia() {
                        this.reiniciarReferencia();
                        this.limpiarError('referencia');
                        const valor = this.form.referencia.trim();
                        if (!valor) {
                            return;
                        }
                        this.referenciaEstado = 'verificando';
                        this.referenciaCheckTimer = setTimeout(async () => {
                            const params = new URLSearchParams({ referencia: valor });
                            if (this.modoEdicion && this.productoId) {
                                params.set('producto_id', this.productoId);
                            }
                            const controlador = new AbortController();
                            this.referenciaAbort = controlador;
                            try {
                                const respuesta = await fetch(`${rutaVerificarReferencia}?${params}`, {
                                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                    signal: controlador.signal,
                                });
                                if (!respuesta.ok) {
                                    throw new Error(`HTTP ${respuesta.status}`);
                                }
                                const datos = await respuesta.json();
                                this.referenciaProducto = datos.producto;
                                this.referenciaEstado = datos.disponible ? 'disponible' : (datos.eliminado ? 'eliminada' : 'ocupada');
                            } catch (e) {
                                if (e.name !== 'AbortError') {
                                    this.referenciaEstado = null;
                                    this.referenciaProducto = null;
                                }
                            }
                        }, 400);
                    },

                    async restaurarProductoEliminado() {
                        const url = this.referenciaProducto?.url_restaurar;
                        if (!url || this.restaurando) {
                            return;
                        }
                        this.restaurando = true;
                        try {
                            const respuesta = await fetch(url, {
                                method: 'PATCH',
                                headers: {
                                    Accept: 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.csrfToken(),
                                },
                            });
                            const datos = await respuesta.json().catch(() => ({}));
                            if (!respuesta.ok) {
                                throw new Error(datos.message || `HTTP ${respuesta.status}`);
                            }
                            this.insertarFila(datos.html);
                            this.totalEliminados = Math.max(0, this.totalEliminados - 1);
                            this.cerrarModal(true);
                            this.mostrarToast(datos.mensaje || 'Producto recuperado.');
                        } catch (e) {
                            this.mostrarToast('No se pudo restaurar el producto. Intenta de nuevo.', true);
                        } finally {
                            this.restaurando = false;
                        }
                    },

                    abrirCategoriaModal() {
                        this.categoriaNombre = '';
                        this.categoriaError = null;
                        this.categoriaModalAbierto = true;
                    },

                    /** Modal de confirmación compartido para Eliminar/Activar/Desactivar. */
                    pedirConfirmacion({ titulo, mensaje, textoBoton, peligroso = false, resumen = null, accion }) {
                        this.confirmacion = { abierta: true, titulo, mensaje, textoBoton, peligroso, resumen, accion };
                    },

                    async confirmarAccion() {
                        const accion = this.confirmacion.accion;
                        if (!accion || this.accionEnCurso) {
                            return;
                        }
                        this.accionEnCurso = true;
                        try {
                            await accion();
                        } finally {
                            this.accionEnCurso = false;
                            this.confirmacion.abierta = false;
                        }
                    },

                    /**
                     * Activar/Desactivar/Eliminar por fetch, sin recargar ni
                     * perder la búsqueda o el filtro. Si la red falla se
                     * envía el form clásico (redirect con mensaje).
                     */
                    async accionDeFila(formulario, tipo, id = null) {
                        let respuesta;
                        try {
                            respuesta = await fetch(formulario.action, {
                                method: 'POST',
                                headers: {
                                    Accept: 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.csrfToken(),
                                },
                                body: new FormData(formulario),
                            });
                        } catch (e) {
                            formulario.submit();
                            return;
                        }
                        const datos = await respuesta.json().catch(() => ({}));
                        if (respuesta.status === 419) {
                            this.mostrarToast('Tu sesión expiró. Recarga la página e intenta de nuevo.', true);
                            return;
                        }
                        if (!respuesta.ok) {
                            this.mostrarToast('No se pudo completar la acción. Intenta de nuevo.', true);
                            return;
                        }
                        if (tipo === 'eliminar') {
                            document.getElementById(`fila-producto-${id}`)?.remove();
                            this.totalEliminados = datos.total_eliminados ?? this.totalEliminados + 1;
                        } else {
                            this.insertarFila(datos.html);
                        }
                        this.mostrarToast(datos.mensaje || 'Listo.');
                    },

                    async abrirVistaRapida(id) {
                        this.vistaRapida = { abierta: true, cargando: true, datos: null };
                        try {
                            const respuesta = await fetch(`${rutaProductos}/${id}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                            if (!respuesta.ok) {
                                throw new Error(`HTTP ${respuesta.status}`);
                            }
                            this.vistaRapida.datos = await respuesta.json();
                        } catch (e) {
                            this.vistaRapida.abierta = false;
                            this.mostrarToast('No se pudo cargar el producto.', true);
                        } finally {
                            this.vistaRapida.cargando = false;
                        }
                    },

                    editarDesdeVistaRapida() {
                        const id = this.vistaRapida.datos?.id;
                        this.vistaRapida.abierta = false;
                        if (id) {
                            this.abrirEditar(id);
                        }
                    },

                    /** Foto del formulario para saber si hay cambios sin guardar. */
                    estadoFormulario() {
                        return JSON.stringify({
                            form: { ...this.form, descripcion: this.quill ? this.quill.root.innerHTML : this.form.descripcion },
                            especificaciones: this.especificaciones,
                            beneficios: this.beneficios,
                            imagenNueva: Boolean(this.imagenArchivo),
                            eliminarImagen: this.eliminarImagenExistente,
                        });
                    },

                    tomarFotoInicial() {
                        this.$nextTick(() => {
                            this.estadoInicial = this.estadoFormulario();
                        });
                    },

                    hayCambiosSinGuardar() {
                        return this.modalAbierto && this.estadoInicial !== null && this.estadoFormulario() !== this.estadoInicial;
                    },

                    cerrarConEscape() {
                        if (this.descarte.abierto) {
                            this.descarte.abierto = false;
                        } else if (this.confirmacion.abierta) {
                            this.confirmacion.abierta = false;
                        } else if (this.categoriaModalAbierto) {
                            this.categoriaModalAbierto = false;
                        } else if (this.vistaRapida.abierta) {
                            this.vistaRapida.abierta = false;
                        } else if (this.lightboxUrl) {
                            this.lightboxUrl = null;
                        } else if (this.modalAbierto) {
                            this.cerrarModal();
                        }
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

                    /**
                     * Espejo de ProductoGuardarRequest para avisar antes de
                     * enviar. No reemplaza al servidor: este sigue validando
                     * todo y sus 422 se muestran igual.
                     */
                    validarEnCliente() {
                        const errores = {};
                        const precio = String(this.form.precio ?? '').trim().replace(/^(\d+),(\d{1,2})$/, '$1.$2');
                        if (precio === '') {
                            errores.precio = ['El campo precio es obligatorio.'];
                        } else if (!/^\d{1,5}(\.\d{1,2})?$/.test(precio)) {
                            errores.precio = ['Escribe el precio con hasta 2 decimales, por ejemplo 149.90.'];
                        } else if (Number(precio) < 0.01) {
                            errores.precio = ['El precio debe ser mayor a S/ 0.00.'];
                        }

                        const textoDescripcion = this.quill ? this.quill.getText().trim() : String(this.form.descripcion || '').replace(/<[^>]*>/g, '').trim();
                        if (!textoDescripcion) {
                            errores.descripcion = ['Escribe una descripción del producto.'];
                        }

                        const specs = [];
                        const vistas = {};
                        this.especificaciones.forEach((fila, i) => {
                            const n = i + 1;
                            const clave = String(fila.clave || '').trim();
                            const valor = String(fila.valor || '').trim();
                            if (!clave && valor) {
                                specs.push(`Fila ${n}: falta la clave.`);
                            }
                            if (clave) {
                                const normalizada = clave.toLowerCase();
                                if (vistas[normalizada]) {
                                    specs.push(`Fila ${n}: la clave «${clave}» ya está en la fila ${vistas[normalizada]}.`);
                                } else {
                                    vistas[normalizada] = n;
                                }
                            }
                        });
                        if (this.especificaciones.length > 50) {
                            specs.push('Máximo 50 filas de especificaciones.');
                        }
                        if (specs.length) {
                            errores.especificaciones = specs;
                        }

                        const precioRegular = String(this.form.precio_comparacion ?? '').trim().replace(/^(\d+),(\d{1,2})$/, '$1.$2');
                        if (precioRegular !== '') {
                            if (!/^\d{1,5}(\.\d{1,2})?$/.test(precioRegular)) {
                                errores.precio_comparacion = ['Escribe el precio regular con hasta 2 decimales, por ejemplo 199.90.'];
                            } else if (!errores.precio && Number(precioRegular) <= Number(precio)) {
                                errores.precio_comparacion = ['El precio regular debe ser mayor al precio de venta.'];
                            }
                        }

                        if (this.form.oferta && !this.form.activo) {
                            errores.oferta = ['Para publicarlo como oferta, activa «Visible en la tienda».'];
                        }
                        if (this.form.oferta && !String(this.form.oferta_titular || '').trim()) {
                            errores.oferta_titular = ['Escribe el titular de la oferta.'];
                        }
                        const beneficios = [];
                        this.beneficios.forEach((b, i) => {
                            if (!String(b.titulo || '').trim() && String(b.texto || '').trim()) {
                                beneficios.push(`Beneficio ${i + 1}: falta el título.`);
                            }
                        });
                        if (beneficios.length) {
                            errores.oferta_beneficios = beneficios;
                        }

                        return errores;
                    },

                    /** "2026-10-29T19:30:00+00:00" → "29/10, 14:30" (hora de Lima, igual que el listado). */
                    formatearVencimiento(iso) {
                        if (!iso) {
                            return '';
                        }
                        return new Date(iso).toLocaleString('es-PE', {
                            timeZone: 'America/Lima',
                            day: '2-digit',
                            month: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false,
                        });
                    },

                    guardar() {
                        if (this.quill) {
                            this.form.descripcion = this.quill.root.innerHTML;
                        }
                        const erroresCliente = this.validarEnCliente();
                        if (Object.keys(erroresCliente).length) {
                            this.errores = erroresCliente;
                            this.tabActiva = this.primeraTabConError();
                            return;
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
                        datos.append('precio_comparacion', this.form.precio_comparacion ?? '');
                        datos.append('oferta', this.form.oferta ? '1' : '0');
                        datos.append('oferta_reiniciar', this.form.oferta_reiniciar ? '1' : '0');
                        datos.append('oferta_titular', this.form.oferta_titular || '');
                        datos.append('oferta_bajada', this.form.oferta_bajada || '');
                        datos.append('oferta_beneficios', JSON.stringify(this.beneficios));
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
                        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
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
                                // Producto que era la oferta anterior: su fila pierde la etiqueta "Oferta".
                                (respuesta.filas_extra || []).forEach((html) => this.insertarFila(html));
                                this.cerrarModal(true);
                                this.mostrarToast(respuesta.mensaje || 'Guardado.');
                            } else if (xhr.status === 422) {
                                this.errores = respuesta.errors || {};
                                if (this.errores.referencia) {
                                    this.reiniciarReferencia();
                                }
                                this.tabActiva = this.primeraTabConError();
                            } else if (xhr.status === 419) {
                                this.mostrarToast('Tu sesión expiró. Recarga la página e intenta de nuevo.', true);
                            } else if (xhr.status === 413) {
                                this.errores = { imagen: ['La imagen es demasiado pesada. Máx. 2 MB.'] };
                                this.tabActiva = 'general';
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
