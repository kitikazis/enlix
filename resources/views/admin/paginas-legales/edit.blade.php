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
            .ql-editor { min-height: 420px; font-size: 14px; }
        </style>
    @endpush

    <div class="mx-auto flex w-full max-w-[900px] flex-col gap-4" x-data="editorPaginaLegal()">

        <div>
            <a href="{{ route('admin.paginas-legales.index') }}" class="text-sm text-text-secondary hover:text-text-primary">← Páginas legales</a>
            <div class="mt-1 flex items-center justify-between gap-3">
                <h1 class="text-xl font-semibold text-text-primary">{{ $pagina->titulo }}</h1>
                <a href="{{ $urlPublica }}" target="_blank" rel="noopener" class="text-xs font-medium text-accent hover:text-accent-hover">Ver página pública →</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-nav border border-danger-strong bg-rechazado-bg px-4 py-3 text-sm text-rechazado-text">
                <strong>Revisa estos campos:</strong>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.paginas-legales.update', $pagina) }}" @submit="sincronizarContenido">
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-4">
                <x-admin.card title="Título">
                    <input
                        type="text" name="titulo" value="{{ old('titulo', $pagina->titulo) }}"
                        class="w-full rounded-nav border border-border px-3 py-2 text-sm text-text-primary"
                        required
                    >
                </x-admin.card>

                <x-admin.card title="Contenido">
                    <div x-ref="editor">{!! old('contenido', $pagina->contenido) !!}</div>
                    <input type="hidden" name="contenido" x-ref="contenidoInput">
                </x-admin.card>

                <x-admin.card title="SEO">
                    <label class="mb-1 block text-xs font-medium text-text-caption">Meta title</label>
                    <input
                        type="text" name="meta_title" value="{{ old('meta_title', $pagina->meta_title) }}"
                        class="mb-3 w-full rounded-nav border border-border px-3 py-2 text-sm text-text-primary"
                    >
                    <label class="mb-1 block text-xs font-medium text-text-caption">Meta description</label>
                    <textarea
                        name="meta_description" rows="2"
                        class="w-full rounded-nav border border-border px-3 py-2 text-sm text-text-primary"
                    >{{ old('meta_description', $pagina->meta_description) }}</textarea>
                </x-admin.card>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex h-11 items-center justify-center rounded-nav bg-accent px-6 text-sm font-medium text-white hover:bg-accent-hover">
                        Guardar cambios
                    </button>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
    <script nonce="{{ $cspNonce }}">
        function editorPaginaLegal() {
            return {
                quill: null,

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
                },
            };
        }
    </script>
    @endpush

</x-layouts.admin-dashboard>
