@php
    $urlPublica = $pagina->slug === \App\Models\PaginaLegal::SLUG_TERMINOS
        ? route('terminos')
        : route('politica-privacidad');
@endphp

<x-layouts.admin-dashboard :titulo="$titulo">

    @push('head')
        {{-- jsdelivr: el unico CDN de terceros que el CSP ya permite en
             script-src/style-src (ver comentario igual en admin/productos/index.blade.php). --}}
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.snow.css">
        <script src="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.min.js"></script>
        <style>
            .ql-editor { min-height: 320px; font-size: 15px; }
            @media (min-width: 64rem) { .ql-editor { min-height: 420px; font-size: 14px; } }
        </style>
    @endpush

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-5 sm:gap-6" x-data="editorPaginaLegal()">

        <x-admin.page-header :title="$pagina->titulo" :back="route('admin.paginas-legales.index')" back-label="Páginas legales">
            <x-slot:actions>
                <a href="{{ $urlPublica }}" target="_blank" rel="noopener" class="btn btn-secondary">
                    <x-admin.icon name="sitio" class="h-4 w-4" />
                    Ver página pública
                </a>
            </x-slot:actions>
        </x-admin.page-header>

        @if ($errors->any())
            <div class="rounded-nav border border-danger-strong bg-rechazado-bg px-4 py-3 text-sm text-rechazado-text" role="alert">
                <strong>Revisa estos campos:</strong>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.paginas-legales.update', $pagina) }}" @submit="sincronizarContenido" class="flex flex-col gap-5 sm:gap-6">
            @csrf
            @method('PUT')

            <x-admin.card title="Título">
                <label for="pagina-titulo" class="sr-only">Título</label>
                <input
                    id="pagina-titulo" type="text" name="titulo" value="{{ old('titulo', $pagina->titulo) }}"
                    class="field-input"
                    @error('titulo') aria-invalid="true" @enderror
                    required
                >
            </x-admin.card>

            <x-admin.card title="Contenido">
                {{-- old() es lo que se acaba de enviar SIN pasar por el mutator: se sanea antes de reimprimirlo. --}}
                <div x-ref="editor">{!! clean(old('contenido', $pagina->contenido)) !!}</div>
                <input type="hidden" name="contenido" x-ref="contenidoInput">
            </x-admin.card>

            <x-admin.card title="SEO">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="min-w-0">
                        <label for="pagina-meta-title" class="field-label">Meta title</label>
                        <input
                            id="pagina-meta-title" type="text" name="meta_title" value="{{ old('meta_title', $pagina->meta_title) }}"
                            class="field-input"
                            @error('meta_title') aria-invalid="true" @enderror
                        >
                    </div>
                    <div class="min-w-0">
                        <label for="pagina-meta-description" class="field-label">Meta description</label>
                        <textarea
                            id="pagina-meta-description" name="meta_description" rows="3"
                            class="field-input"
                            @error('meta_description') aria-invalid="true" @enderror
                        >{{ old('meta_description', $pagina->meta_description) }}</textarea>
                    </div>
                </div>
            </x-admin.card>

            {{-- Guardar: barra fija al pie en móvil (el editor es largo y el botón quedaba al final de todo). --}}
            <div class="sticky bottom-0 z-20 -mx-4 -mb-5 flex border-t border-border bg-card px-4 py-3 sm:static sm:mx-0 sm:mb-0 sm:justify-end sm:border-0 sm:bg-transparent sm:p-0">
                <button type="submit" class="btn btn-primary w-full sm:w-auto sm:px-6" :aria-busy="enviando ? 'true' : 'false'">
                    <span x-text="enviando ? 'Guardando…' : 'Guardar cambios'">Guardar cambios</span>
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script nonce="{{ $cspNonce }}">
        function editorPaginaLegal() {
            return {
                quill: null,
                enviando: false,

                init() {
                    // Igual criterio que el editor de productos: si Quill no
                    // carga (CDN caido, bloqueador), el formulario sigue
                    // funcionando con el HTML que ya estaba.
                    try {
                        this.quill = new Quill(this.$refs.editor, {
                            theme: 'snow',
                            modules: {
                                toolbar: [
                                    [{ header: [2, 3, false] }],
                                    ['bold', 'italic', 'underline'],
                                    [{ list: 'ordered' }, { list: 'bullet' }],
                                    ['blockquote', 'link'],
                                    ['clean'],
                                ],
                            },
                        });
                    } catch (e) {
                        console.error('No se pudo inicializar el editor de contenido (Quill):', e);
                    }
                },

                sincronizarContenido() {
                    // Si Quill no cargo, $refs.editor sigue mostrando el HTML
                    // original tal cual (ver init()): se manda eso en vez de
                    // dejar el campo vacio y borrar el contenido guardado.
                    this.$refs.contenidoInput.value = this.quill
                        ? this.quill.root.innerHTML
                        : this.$refs.editor.innerHTML;
                    this.enviando = true;
                },
            };
        }
    </script>
    @endpush

</x-layouts.admin-dashboard>
