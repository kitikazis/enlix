<x-layouts.admin-dashboard :titulo="'Páginas legales - Enlix Admin'">
    <div class="mx-auto flex w-full max-w-[900px] flex-col gap-4">

        <div>
            <h1 class="text-xl font-semibold text-text-primary">Páginas legales</h1>
            <p class="text-sm text-text-secondary">Edita el contenido público de estas páginas sin tocar código.</p>
        </div>

        @if (session('status'))
            <div class="rounded-nav border border-pagado-dot bg-pagado-bg px-4 py-3 text-sm text-pagado-text">
                {{ session('status') }}
            </div>
        @endif

        <x-admin.card :padded="false">
            @foreach ($paginas as $pagina)
                <a
                    href="{{ route('admin.paginas-legales.edit', $pagina) }}"
                    class="flex items-center justify-between gap-3 border-b border-border px-5 py-4 last:border-b-0 hover:bg-page"
                >
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-text-primary">{{ $pagina->titulo }}</p>
                        <p class="truncate text-xs text-text-caption">/{{ $pagina->slug }} · actualizado {{ $pagina->updated_at->diffForHumans() }}</p>
                    </div>
                    <span class="shrink-0 text-xs font-medium text-accent">Editar →</span>
                </a>
            @endforeach
        </x-admin.card>

    </div>
</x-layouts.admin-dashboard>
