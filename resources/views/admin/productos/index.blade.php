<x-layouts.admin-dashboard :titulo="'Productos - Enlix Admin'">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">

        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <h1 class="text-2xl font-semibold text-text-primary md:text-[28px]">Productos</h1>
            <a href="{{ route('admin.productos.create') }}" class="inline-flex h-10 items-center justify-center rounded-nav bg-accent px-4 text-sm font-medium text-white hover:bg-accent-hover">
                + Nuevo producto
            </a>
        </div>

        @if (session('exito'))
            <div class="rounded-nav bg-pagado-bg px-4 py-3 text-sm font-medium text-pagado-text">
                {{ session('exito') }}
            </div>
        @endif

        <x-admin.card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-border text-xs text-text-caption">
                            <th class="py-3 pl-5 pr-3 font-medium">Orden</th>
                            <th class="py-3 pr-3 font-medium">Nombre</th>
                            <th class="py-3 pr-3 font-medium">Slug</th>
                            <th class="py-3 pr-3 text-right font-medium">Precio</th>
                            <th class="py-3 pr-3 font-medium">Estado</th>
                            <th class="py-3 pr-5 text-right font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($productos as $producto)
                            <tr>
                                <td class="py-2.5 pl-5 pr-3 font-mono text-xs text-text-secondary">{{ $producto->orden }}</td>
                                <td class="py-2.5 pr-3 font-medium text-text-primary">{{ $producto->nombre }}</td>
                                <td class="py-2.5 pr-3 font-mono text-xs text-text-caption">{{ $producto->slug }}</td>
                                <td class="whitespace-nowrap py-2.5 pr-3 text-right font-mono">S/ {{ number_format($producto->precio_centimos / 100, 2) }}</td>
                                <td class="whitespace-nowrap py-2.5 pr-3">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $producto->activo ? 'bg-pagado-bg text-pagado-text' : 'bg-anulado-bg text-anulado-text' }}">
                                        {{ $producto->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap py-2.5 pr-5 text-right">
                                    <a href="{{ route('admin.productos.edit', $producto) }}" class="text-xs font-medium text-accent hover:text-accent-hover">Editar</a>
                                    <form method="POST" action="{{ route('admin.productos.alternar-activo', $producto) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="ml-3 text-xs font-medium text-text-secondary hover:text-text-primary">
                                            {{ $producto->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8">
                                    <x-admin.empty-state title="No hay productos todavía." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    </div>
</x-layouts.admin-dashboard>
