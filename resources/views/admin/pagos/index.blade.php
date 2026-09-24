@extends('layouts.admin')

@php
  use App\Enums\EstadoPago;

  $badge = [
    EstadoPago::Pendiente->value => 'text-bg-warning',
    EstadoPago::EnVerificacion->value => 'text-bg-info',
    EstadoPago::Pagado->value => 'text-bg-success',
    EstadoPago::Rechazado->value => 'text-bg-danger',
    EstadoPago::Expirado->value => 'text-bg-secondary',
  ];
@endphp

@section('content')
<h1 class="h4 mb-3">Pagos</h1>

<div class="d-flex flex-wrap gap-2 mb-3">
  @foreach ($estados as $estado)
    <span class="badge {{ $badge[$estado->value] }}">{{ $estado->etiqueta() }}: {{ $resumen[$estado->value] ?? 0 }}</span>
  @endforeach
</div>

<form method="GET" class="row g-2 align-items-end mb-3">
  <div class="col-auto">
    <label class="form-label mb-0 small">Estado</label>
    <select name="estado" class="form-select form-select-sm">
      <option value="">Todos</option>
      @foreach ($estados as $estado)
        <option value="{{ $estado->value }}" @selected(($filtros['estado'] ?? '') === $estado->value)>{{ $estado->etiqueta() }}</option>
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
        <th class="text-nowrap">Fecha</th>
        <th>Producto</th>
        <th>Email</th>
        <th class="text-end text-nowrap">Monto</th>
        <th>Estado</th>
        <th class="text-nowrap">Tarjeta</th>
        <th>Order ID</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($pagos as $pago)
        <tr>
          <td class="text-nowrap">{{ $pago->created_at->format('d/m/Y H:i') }}</td>
          <td class="text-nowrap">{{ $pago->producto }}</td>
          <td class="text-truncate" style="max-width: 180px;" title="{{ $pago->email }}">{{ $pago->email }}</td>
          <td class="text-end text-nowrap">{{ $pago->moneda }} {{ number_format($pago->monto / 100, 2) }}</td>
          <td class="text-nowrap"><span class="badge {{ $badge[$pago->estado->value] ?? 'text-bg-light' }}">{{ $pago->estado->etiqueta() }}</span></td>
          <td class="text-nowrap">{{ $pago->card_brand ? $pago->card_brand.' '.$pago->card_masked_pan : '—' }}</td>
          <td class="text-muted small text-truncate" style="max-width: 140px;" title="{{ $pago->izipay_order_id }}">{{ $pago->izipay_order_id }}</td>
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
