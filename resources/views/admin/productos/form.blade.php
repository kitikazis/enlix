<x-layouts.admin-dashboard :titulo="($producto->exists ? 'Editar' : 'Nuevo').' producto - Enlix Admin'">
    <div class="mx-auto flex max-w-3xl flex-col gap-6">

        <h1 class="text-2xl font-semibold text-text-primary md:text-[28px]">
            {{ $producto->exists ? 'Editar producto' : 'Nuevo producto' }}
        </h1>

        @if ($errors->any())
            <div class="rounded-nav bg-rechazado-bg px-4 py-3 text-sm text-rechazado-text">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-admin.card>
            <form method="POST" action="{{ $accion }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                @csrf
                @if ($producto->exists)
                    @method('PUT')
                @endif

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-text-primary">Categoría</label>
                        <select name="categoria_id" class="h-10 w-full rounded-nav border border-border bg-card px-3 text-sm">
                            <option value="">Sin categoría</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}" @selected((int) old('categoria_id', $producto->categoria_id) === $categoria->id)>{{ $categoria->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-text-primary">Marca</label>
                        <select name="marca_id" class="h-10 w-full rounded-nav border border-border bg-card px-3 text-sm">
                            <option value="">Sin marca</option>
                            @foreach ($marcas as $marca)
                                <option value="{{ $marca->id }}" @selected((int) old('marca_id', $producto->marca_id) === $marca->id)>{{ $marca->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Nombre</label>
                    <input type="text" name="nombre" class="h-10 w-full rounded-nav border border-border bg-card px-3 text-sm" value="{{ old('nombre', $producto->nombre) }}" required maxlength="100">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">SKU <span class="font-normal text-text-caption">(opcional, único)</span></label>
                    <input type="text" name="sku" class="h-10 w-full max-w-xs rounded-nav border border-border bg-card px-3 text-sm" value="{{ old('sku', $producto->sku) }}" maxlength="50">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Descripción corta <span class="font-normal text-text-caption">(para la tarjeta del catálogo)</span></label>
                    <input type="text" name="descripcion_corta" class="h-10 w-full rounded-nav border border-border bg-card px-3 text-sm" value="{{ old('descripcion_corta', $producto->descripcion_corta) }}" maxlength="160">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Descripción</label>
                    <textarea name="descripcion" rows="3" required maxlength="500" class="w-full rounded-nav border border-border bg-card px-3 py-2 text-sm">{{ old('descripcion', $producto->descripcion) }}</textarea>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-text-primary">Precio (S/)</label>
                        <input type="number" name="precio" step="0.01" min="0.01" max="99999.99" required
                               class="h-10 w-full rounded-nav border border-border bg-card px-3 text-sm"
                               value="{{ old('precio', $producto->exists ? number_format($producto->precio_centimos / 100, 2, '.', '') : '') }}">
                        <p class="mt-1 text-xs text-text-caption">Este es el monto real que se le va a cobrar al cliente en el checkout.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-text-primary">Stock</label>
                        <input type="number" name="stock" min="0" class="h-10 w-full rounded-nav border border-border bg-card px-3 text-sm" value="{{ old('stock', $producto->stock ?? 0) }}">
                        @if ($producto->exists && $producto->stock_reservado > 0)
                            <p class="mt-1 text-xs text-text-caption">{{ $producto->stock_reservado }} unidad(es) reservada(s) en pedidos pendientes.</p>
                        @endif
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Especificaciones <span class="font-normal text-text-caption">(una "clave: valor" por línea, ej. "Socket: AM5")</span></label>
                    <textarea name="especificaciones" rows="4" maxlength="2000" class="w-full rounded-nav border border-border bg-card px-3 py-2 font-mono text-sm">{{ old('especificaciones', $producto->exists && $producto->especificaciones ? collect($producto->especificaciones)->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n") : '') }}</textarea>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Características (una por línea)</label>
                    <textarea name="features" rows="4" maxlength="2000" class="w-full rounded-nav border border-border bg-card px-3 py-2 text-sm">{{ old('features', $producto->exists ? implode("\n", $producto->features ?? []) : '') }}</textarea>
                </div>

                @if ($producto->exists && $producto->imagenes->isNotEmpty())
                    <div>
                        <label class="mb-2 block text-sm font-medium text-text-primary">Imágenes actuales</label>
                        <div class="flex flex-wrap gap-4">
                            @foreach ($producto->imagenes as $imagen)
                                <div class="w-24 text-center">
                                    <img src="{{ asset('storage/'.$imagen->ruta) }}" alt="{{ $imagen->texto_alternativo }}" class="mb-1.5 h-24 w-24 rounded-nav border border-border object-cover">
                                    <label class="flex items-center justify-center gap-1.5 text-xs text-rechazado-text">
                                        <input type="checkbox" name="eliminar_imagenes[]" value="{{ $imagen->id }}" class="h-3.5 w-3.5 rounded border-border">
                                        Eliminar
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Agregar imágenes <span class="font-normal text-text-caption">(JPG/PNG/WEBP, máx. 4MB cada una, hasta 8)</span></label>
                    <input type="file" name="imagenes[]" accept="image/jpeg,image/png,image/webp" multiple class="block w-full text-sm text-text-secondary file:mr-3 file:rounded-nav file:border-0 file:bg-page file:px-3 file:py-2 file:text-sm file:font-medium file:text-text-primary hover:file:bg-border">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-text-primary">Orden de exhibición</label>
                    <input type="number" name="orden" min="0" class="h-10 w-32 rounded-nav border border-border bg-card px-3 text-sm" value="{{ old('orden', $producto->orden ?? 0) }}">
                </div>

                <label class="flex items-center gap-2 text-sm text-text-primary">
                    <input type="checkbox" name="activo" value="1" class="h-4 w-4 rounded border-border" {{ old('activo', $producto->activo ?? true) ? 'checked' : '' }}>
                    Visible en /productos
                </label>

                <label class="flex items-center gap-2 text-sm text-text-primary">
                    <input type="checkbox" name="destacado" value="1" class="h-4 w-4 rounded border-border" {{ old('destacado', $producto->destacado ?? false) ? 'checked' : '' }}>
                    Destacado
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-nav bg-accent px-4 text-sm font-medium text-white hover:bg-accent-hover">Guardar</button>
                    <a href="{{ route('admin.productos.index') }}" class="inline-flex h-10 items-center justify-center rounded-nav border border-border px-4 text-sm font-medium text-text-secondary hover:bg-page">Cancelar</a>
                </div>
            </form>
        </x-admin.card>
    </div>
</x-layouts.admin-dashboard>
