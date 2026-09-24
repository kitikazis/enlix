@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Productos</h1>
  <a href="{{ route('admin.productos.create') }}" class="btn btn-primary btn-sm">+ Nuevo producto</a>
</div>

@if (session('exito'))
  <div class="alert alert-success py-2">{{ session('exito') }}</div>
@endif

<div class="table-responsive bg-white rounded shadow-sm">
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>Orden</th>
        <th>Nombre</th>
        <th>Slug</th>
        <th class="text-end">Precio</th>
        <th>Estado</th>
        <th class="text-end">Acciones</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($productos as $producto)
        <tr>
          <td>{{ $producto->orden }}</td>
          <td>{{ $producto->nombre }}</td>
          <td class="text-muted small">{{ $producto->slug }}</td>
          <td class="text-end">S/ {{ number_format($producto->precio_centimos / 100, 2) }}</td>
          <td>
            <span class="badge {{ $producto->activo ? 'text-bg-success' : 'text-bg-secondary' }}">
              {{ $producto->activo ? 'Activo' : 'Inactivo' }}
            </span>
          </td>
          <td class="text-end">
            <a href="{{ route('admin.productos.edit', $producto) }}" class="btn btn-sm btn-outline-primary">Editar</a>
            <form method="POST" action="{{ route('admin.productos.alternar-activo', $producto) }}" class="d-inline">
              @csrf
              @method('PATCH')
              <button type="submit" class="btn btn-sm btn-outline-secondary">
                {{ $producto->activo ? 'Desactivar' : 'Activar' }}
              </button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="text-center text-muted py-4">No hay productos todavía.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
