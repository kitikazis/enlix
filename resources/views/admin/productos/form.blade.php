@extends('layouts.admin')

@section('content')
<h1 class="h4 mb-3">{{ $producto->exists ? 'Editar producto' : 'Nuevo producto' }}</h1>

@if ($errors->any())
  <div class="alert alert-danger py-2">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="card shadow-sm" style="max-width: 720px;">
  <div class="card-body">
    <form method="POST" action="{{ $accion }}" enctype="multipart/form-data">
      @csrf
      @if ($producto->exists)
        @method('PUT')
      @endif

      <div class="row g-3 mb-3">
        <div class="col-sm-6">
          <label class="form-label">Categoría</label>
          <select name="categoria_id" class="form-select">
            <option value="">Sin categoría</option>
            @foreach ($categorias as $categoria)
              <option value="{{ $categoria->id }}" @selected((int) old('categoria_id', $producto->categoria_id) === $categoria->id)>{{ $categoria->nombre }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-sm-6">
          <label class="form-label">Marca</label>
          <select name="marca_id" class="form-select">
            <option value="">Sin marca</option>
            @foreach ($marcas as $marca)
              <option value="{{ $marca->id }}" @selected((int) old('marca_id', $producto->marca_id) === $marca->id)>{{ $marca->nombre }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $producto->nombre) }}" required maxlength="100">
      </div>

      <div class="mb-3">
        <label class="form-label">SKU <span class="text-muted">(opcional, único)</span></label>
        <input type="text" name="sku" class="form-control" style="max-width: 240px;" value="{{ old('sku', $producto->sku) }}" maxlength="50">
      </div>

      <div class="mb-3">
        <label class="form-label">Descripción corta <span class="text-muted">(para la tarjeta del catálogo)</span></label>
        <input type="text" name="descripcion_corta" class="form-control" value="{{ old('descripcion_corta', $producto->descripcion_corta) }}" maxlength="160">
      </div>

      <div class="mb-3">
        <label class="form-label">Descripción</label>
        <textarea name="descripcion" class="form-control" rows="3" required maxlength="500">{{ old('descripcion', $producto->descripcion) }}</textarea>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-sm-6">
          <label class="form-label">Precio (S/)</label>
          <input type="number" name="precio" class="form-control" step="0.01" min="0.01" max="99999.99"
                 value="{{ old('precio', $producto->exists ? number_format($producto->precio_centimos / 100, 2, '.', '') : '') }}" required>
          <div class="form-text">Este es el monto real que se le va a cobrar al cliente en el checkout.</div>
        </div>
        <div class="col-sm-6">
          <label class="form-label">Stock</label>
          <input type="number" name="stock" class="form-control" min="0" value="{{ old('stock', $producto->stock ?? 0) }}">
          @if ($producto->exists && $producto->stock_reservado > 0)
            <div class="form-text">{{ $producto->stock_reservado }} unidad(es) reservada(s) en pedidos pendientes.</div>
          @endif
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Especificaciones <span class="text-muted">(una "clave: valor" por línea, ej. "Socket: AM5")</span></label>
        <textarea name="especificaciones" class="form-control" rows="4" maxlength="2000">{{ old('especificaciones', $producto->exists && $producto->especificaciones ? collect($producto->especificaciones)->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n") : '') }}</textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Características (una por línea)</label>
        <textarea name="features" class="form-control" rows="4" maxlength="2000">{{ old('features', $producto->exists ? implode("\n", $producto->features ?? []) : '') }}</textarea>
      </div>

      @if ($producto->exists && $producto->imagenes->isNotEmpty())
        <div class="mb-3">
          <label class="form-label d-block">Imágenes actuales</label>
          <div class="d-flex flex-wrap gap-3">
            @foreach ($producto->imagenes as $imagen)
              <div class="text-center" style="width: 100px;">
                <img src="{{ asset('storage/'.$imagen->ruta) }}" alt="{{ $imagen->texto_alternativo }}" class="img-thumbnail mb-1" style="width: 100px; height: 100px; object-fit: cover;">
                <div class="form-check d-flex justify-content-center gap-1">
                  <input type="checkbox" name="eliminar_imagenes[]" value="{{ $imagen->id }}" id="img{{ $imagen->id }}" class="form-check-input">
                  <label for="img{{ $imagen->id }}" class="form-check-label small text-danger">Eliminar</label>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endif

      <div class="mb-3">
        <label class="form-label">Agregar imágenes <span class="text-muted">(JPG/PNG/WEBP, máx. 4MB cada una, hasta 8)</span></label>
        <input type="file" name="imagenes[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
      </div>

      <div class="mb-3">
        <label class="form-label">Orden de exhibición</label>
        <input type="number" name="orden" class="form-control" min="0" style="max-width: 120px;" value="{{ old('orden', $producto->orden ?? 0) }}">
      </div>

      <div class="form-check mb-2">
        <input type="checkbox" name="activo" id="activo" class="form-check-input" value="1" {{ old('activo', $producto->activo ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="activo">Visible en /productos</label>
      </div>

      <div class="form-check mb-4">
        <input type="checkbox" name="destacado" id="destacado" class="form-check-input" value="1" {{ old('destacado', $producto->destacado ?? false) ? 'checked' : '' }}>
        <label class="form-check-label" for="destacado">Destacado</label>
      </div>

      <button type="submit" class="btn btn-primary">Guardar</button>
      <a href="{{ route('admin.productos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    </form>
  </div>
</div>
@endsection
