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

<div class="card shadow-sm" style="max-width: 640px;">
  <div class="card-body">
    <form method="POST" action="{{ $accion }}">
      @csrf
      @if ($producto->exists)
        @method('PUT')
      @endif

      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $producto->nombre) }}" required maxlength="100">
      </div>

      <div class="mb-3">
        <label class="form-label">Descripción</label>
        <textarea name="descripcion" class="form-control" rows="3" required maxlength="500">{{ old('descripcion', $producto->descripcion) }}</textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Precio (S/)</label>
        <input type="number" name="precio" class="form-control" step="0.01" min="0.01" max="99999.99"
               value="{{ old('precio', $producto->exists ? number_format($producto->precio_centimos / 100, 2, '.', '') : '') }}" required>
        <div class="form-text">Este es el monto real que se le va a cobrar al cliente en el checkout.</div>
      </div>

      <div class="mb-3">
        <label class="form-label">Características (una por línea)</label>
        <textarea name="features" class="form-control" rows="4" maxlength="2000">{{ old('features', $producto->exists ? implode("\n", $producto->features ?? []) : '') }}</textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Orden de exhibición</label>
        <input type="number" name="orden" class="form-control" min="0" style="max-width: 120px;" value="{{ old('orden', $producto->orden ?? 0) }}">
      </div>

      <div class="form-check mb-4">
        <input type="checkbox" name="activo" id="activo" class="form-check-input" value="1" {{ old('activo', $producto->activo ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="activo">Visible en /productos</label>
      </div>

      <button type="submit" class="btn btn-primary">Guardar</button>
      <a href="{{ route('admin.productos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    </form>
  </div>
</div>
@endsection
