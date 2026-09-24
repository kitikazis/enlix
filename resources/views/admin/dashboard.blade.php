@extends('layouts.admin')

@php
  use App\Enums\EstadoPago;

  $colorEstado = [
    EstadoPago::Pendiente->value => 'text-bg-warning',
    EstadoPago::EnVerificacion->value => 'text-bg-info',
    EstadoPago::Pagado->value => 'text-bg-success',
    EstadoPago::Rechazado->value => 'text-bg-danger',
    EstadoPago::Anulado->value => 'text-bg-dark',
    EstadoPago::Expirado->value => 'text-bg-secondary',
  ];
@endphp

@section('content')
<h1 class="h4 mb-4">Dashboard</h1>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Ingresos hoy</div>
        <div class="fs-4 fw-bold">S/ {{ number_format($ingresosHoy / 100, 2) }}</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Ingresos este mes</div>
        <div class="fs-4 fw-bold">S/ {{ number_format($ingresosMes / 100, 2) }}</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Ingresos totales</div>
        <div class="fs-4 fw-bold">S/ {{ number_format($ingresosTotal / 100, 2) }}</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100 shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Tasa de conversión</div>
        <div class="fs-4 fw-bold">{{ $tasaConversion }}%</div>
        <div class="text-muted small">de intentos que terminan pagados</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card h-100 shadow-sm">
      <div class="card-header bg-white">Pagos por estado</div>
      <div class="card-body">
        @foreach ($estados as $estado)
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="badge {{ $colorEstado[$estado->value] }}">{{ $estado->etiqueta() }}</span>
            <span class="fw-bold">{{ $porEstado[$estado->value] ?? 0 }}</span>
          </div>
        @endforeach
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100 shadow-sm">
      <div class="card-header bg-white">Ingresos por producto</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light">
            <tr><th>Producto</th><th class="text-end">Ventas</th><th class="text-end">Total</th></tr>
          </thead>
          <tbody>
            @forelse ($ingresosPorProducto as $fila)
              <tr>
                <td>{{ $fila->producto }}</td>
                <td class="text-end">{{ $fila->ventas }}</td>
                <td class="text-end">S/ {{ number_format($fila->total / 100, 2) }}</td>
              </tr>
            @empty
              <tr><td colspan="3" class="text-center text-muted py-3">Sin ventas todavía.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <span>Últimos pagos</span>
    <a href="{{ route('admin.pagos.index') }}" class="small">Ver todos →</a>
  </div>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead class="table-light">
        <tr><th>Fecha</th><th>Producto</th><th>Email</th><th class="text-end">Monto</th><th>Estado</th></tr>
      </thead>
      <tbody>
        @forelse ($ultimosPagos as $pago)
          <tr>
            <td>{{ $pago->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $pago->producto }}</td>
            <td>{{ $pago->email }}</td>
            <td class="text-end">{{ $pago->moneda ?? 'PEN' }} {{ number_format($pago->monto / 100, 2) }}</td>
            <td><span class="badge {{ $colorEstado[$pago->estado->value] ?? 'text-bg-light' }}">{{ $pago->estado->etiqueta() }}</span></td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center text-muted py-3">No hay pagos todavía.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body d-flex justify-content-between align-items-center">
    <div>
      <div class="text-muted small">Productos</div>
      <div class="fw-bold">{{ $totalProductosActivos }} activos de {{ $totalProductos }} en total</div>
    </div>
    <a href="{{ route('admin.productos.index') }}" class="btn btn-sm btn-primary">Gestionar productos</a>
  </div>
</div>
@endsection
