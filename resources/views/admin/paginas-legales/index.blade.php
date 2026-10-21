<x-layouts.admin-dashboard :titulo="'Páginas legales - Enlix Admin'">
    <div class="mx-auto flex w-full max-w-4xl flex-col gap-5 sm:gap-6">

        <x-admin.page-header title="Páginas legales" subtitle="Edita el contenido público de estas páginas sin tocar código." />

        @if (session('status'))
            <div class="rounded-nav border border-pagado-dot bg-pagado-bg px-4 py-3 text-sm text-pagado-text" role="status">
                {{ session('status') }}
            </div>
        @endif

        <x-admin.card :padded="false">
            <ul class="divide-y divide-border" aria-label="Páginas legales">
                @forelse ($paginas as $pagina)
                    <li>
                        <a
                            href="{{ route('admin.paginas-legales.edit', $pagina) }}"
                            class="flex min-h-touch items-center justify-between gap-3 px-4 py-4 transition-colors hover:bg-row-hover focus-visible:bg-row-hover sm:px-5"
                        >
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-nav bg-page text-text-secondary sm:flex">
                                    <x-admin.icon name="legal" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-text-primary">{{ $pagina->titulo }}</span>
                                    <span class="block truncate text-xs text-text-secondary">/{{ $pagina->slug }} · actualizado {{ $pagina->updated_at->diffForHumans() }}</span>
                                </span>
                            </span>
                            <span class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-accent">
                                Editar <x-admin.icon name="flecha-der" class="h-4 w-4" />
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="p-4 sm:p-5">
                        <x-admin.empty-state icon="legal" title="No hay páginas legales" description="Se crean al ejecutar el seeder de páginas legales." />
                    </li>
                @endforelse
            </ul>
        </x-admin.card>

    </div>
</x-layouts.admin-dashboard>
