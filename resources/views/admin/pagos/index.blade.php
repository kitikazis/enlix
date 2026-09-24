@extends('layouts.admin')

@php
  $badge = [
    'pendiente' => 'text-bg-warning',
    'pagado' => 'text-bg-success',
    'rechazado' => 'text-bg-danger',
    'expirado' => 'text-bg-secondary',
  ];
@endphp

@section('content')
<h1 class="h4 mb-3">Pagos</h1>

<div class="d-flex flex-wrap gap-2 mb-3">
  @foreach ($estados as $estado)
    <span class="badge {{ $badge[$estado] }}">{{ ucfirst($estado) }}: {{ $resumen[$estado] ?? 0 }}</span>
  @endforeach
</div>

<form method="GET" class="row g-2 align-items-end mb-3">
  <div class="col-auto">
    <label class="form-label mb-0 small">Estado</label>
    <select name="estado" class="form-select form-select-sm">
      <option value="">Todos</option>
      @foreach ($estados as $estado)
        <option value="{{ $estado }}" @selected(($filtros['estado'] ?? '') === $estado)>{{ ucfirst($estado) }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-auto">
    <label class="form-label mb-0 small">Desde</label>
    <input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" class="form-control form-control-sm">
  </div>
  <div class="col-auto">
    <label class="form-label mb-0 small">Hasta</label>
    <input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" class="form-control form-control-sm">
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
    <a href="{{ route('admin.pagos.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
  </div>
</form>

<div class="table-responsive bg-white rounded shadow-sm">
  <table class="table table-sm table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>Fecha</th>
        <th>Producto</th>
        <th>Email</th>
        <th class="text-end">Monto</th>
        <th>Estado</th>
        <th>Tarjeta</th>
        <th>Order ID</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($pagos as $pago)
        <tr>
          <td>{{ $pago->created_at->format('d/m/Y H:i') }}</td>
          <td>{{ $pago->producto }}</td>
          <td>{{ $pago->email }}</td>
          <td class="text-end">{{ $pago->moneda }} {{ number_format($pago->monto / 100, 2) }}</td>
          <td><span class="badge {{ $badge[$pago->estado] ?? 'text-bg-light' }}">{{ ucfirst($pago->estado) }}</span></td>
          <td>{{ $pago->card_brand ? $pago->card_brand.' '.$pago->card_masked_pan : '—' }}</td>
          <td class="text-muted small">{{ $pago->izipay_order_id }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="text-center text-muted py-4">No hay pagos con estos filtros.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-3">
  {{ $pagos->links() }}
</div>
@endsection
