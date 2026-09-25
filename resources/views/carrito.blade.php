@extends('layouts.app')

@section('content')

<style>
  .carrito-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 0;
    border-bottom: 1px solid var(--enlix-border);
  }
  .carrito-item-info { flex: 1 1 auto; min-width: 0; }
  .carrito-item-nombre { font-size: 16px; margin: 0 0 4px; color: var(--enlix-ink); }
  .carrito-item-precio { font-size: 13px; color: var(--enlix-muted); margin: 0; }

  .carrito-item-cantidad {
    display: flex; align-items: center; gap: 10px; flex: none;
  }
  .btn-cantidad {
    width: 30px; height: 30px; border-radius: 6px;
    border: 1px solid var(--enlix-border); background: #fff;
    font-size: 16px; line-height: 1; color: var(--enlix-ink);
    display: flex; align-items: center; justify-content: center;
  }
  .btn-cantidad:hover { background: #f5f7fa; }
  .carrito-item-cantidad-valor { min-width: 20px; text-align: center; font-weight: 600; }

  .carrito-item-subtotal { flex: none; width: 100px; text-align: right; font-weight: 700; color: var(--enlix-ink); }

  .carrito-item-eliminar {
    flex: none; border: none; background: none; color: var(--enlix-muted);
    font-size: 20px; line-height: 1; padding: 4px 8px;
  }
  .carrito-item-eliminar:hover { color: #d64545; }

  .carrito-resumen {
    background: #fff; border: 1px solid var(--enlix-border); border-radius: 8px;
    padding: 24px; position: sticky; top: 100px;
  }
  .carrito-resumen h2 { font-size: 18px; margin: 0 0 16px; }
  .carrito-resumen-linea { display: flex; justify-content: space-between; font-size: 15px; color: var(--enlix-ink); }
  .carrito-resumen-linea.total { font-size: 20px; font-weight: 700; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--enlix-border); }
</style>

<section class="section-pad">
  <div class="container">

    <div class="mb-5">
      <p class="svc-block-num">Tienda</p>
      <h1>Tu carrito</h1>
    </div>

    <div id="carrito-vacio" class="text-center py-5" style="{{ $resumen['items']->isEmpty() ? '' : 'display:none;' }}">
      <p style="color: var(--enlix-muted);">Tu carrito está vacío.</p>
      <a href="{{ route('productos') }}" class="btn btn-primary-enlix">Ver productos</a>
    </div>

    <div id="carrito-contenido" class="row g-4" style="{{ $resumen['items']->isEmpty() ? 'display:none;' : '' }}">
      <div class="col-lg-8">
        <div id="carrito-items">
          @foreach ($resumen['items'] as $item)
            <div class="carrito-item" data-item-id="{{ $item['id'] }}" data-stock="{{ $item['stock_disponible'] }}">
              <div class="carrito-item-info">
                <h3 class="carrito-item-nombre">{{ $item['nombre'] }}</h3>
                <p class="carrito-item-precio">S/ {{ number_format($item['precio_unitario_centimos'] / 100, 2) }} c/u</p>
                <p class="carrito-item-error text-danger mb-0" style="font-size: 12.5px; display: none;"></p>
              </div>
              <div class="carrito-item-cantidad">
                <button type="button" class="btn-cantidad" data-accion="restar" aria-label="Restar una unidad de {{ $item['nombre'] }}">−</button>
                <span class="carrito-item-cantidad-valor" aria-live="polite">{{ $item['cantidad'] }}</span>
                <button type="button" class="btn-cantidad" data-accion="sumar" aria-label="Agregar una unidad de {{ $item['nombre'] }}">+</button>
              </div>
              <div class="carrito-item-subtotal">S/ {{ number_format($item['subtotal_centimos'] / 100, 2) }}</div>
              <button type="button" class="carrito-item-eliminar" aria-label="Eliminar {{ $item['nombre'] }} del carrito">×</button>
            </div>
          @endforeach
        </div>
      </div>

      <div class="col-lg-4">
        <div class="carrito-resumen">
          <h2>Resumen del pedido</h2>
          <div class="carrito-resumen-linea total">
            <span>Total</span>
            <span id="carrito-total">S/ {{ number_format($resumen['subtotal_centimos'] / 100, 2) }}</span>
          </div>
          <button type="button" class="btn btn-primary-enlix w-100 mt-3" disabled title="El pago se habilita en la siguiente fase (checkout)">
            Continuar al pago (próximamente)
          </button>
          <a href="{{ route('productos') }}" class="d-block text-center mt-3" style="font-size: 14px;">Seguir comprando</a>
        </div>
      </div>
    </div>

  </div>
</section>

@push('scripts')
<script nonce="{{ $cspNonce }}">
  const CSRF_TOKEN = @json(csrf_token());
  const URL_ITEMS = @json(route('carrito.items.store'));
  const formateadorSoles = new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' });

  document.querySelectorAll('.carrito-item').forEach(function (fila) {
    const itemId = fila.dataset.itemId;
    const stock = parseInt(fila.dataset.stock, 10);
    const valorEl = fila.querySelector('.carrito-item-cantidad-valor');
    const subtotalEl = fila.querySelector('.carrito-item-subtotal');
    const errorEl = fila.querySelector('.carrito-item-error');

    fila.querySelector('[data-accion="restar"]').addEventListener('click', function () {
      const actual = parseInt(valorEl.textContent, 10);
      if (actual <= 1) return;
      actualizarCantidad(actual - 1);
    });

    fila.querySelector('[data-accion="sumar"]').addEventListener('click', function () {
      const actual = parseInt(valorEl.textContent, 10);
      if (actual >= stock) {
        mostrarError('No hay más stock disponible.');
        return;
      }
      actualizarCantidad(actual + 1);
    });

    fila.querySelector('.carrito-item-eliminar').addEventListener('click', function () {
      fetchJson('DELETE', URL_ITEMS + '/' + itemId, null).then(function (data) {
        if (!data.ok) return;
        fila.remove();
        actualizarTotal(data);
        if (data.cantidad_total < 1) mostrarCarritoVacio();
      });
    });

    function actualizarCantidad(nuevaCantidad) {
      ocultarError();
      fetchJson('PATCH', URL_ITEMS + '/' + itemId, { cantidad: nuevaCantidad }).then(function (data) {
        if (!data.ok) {
          mostrarError(data.mensaje || 'No se pudo actualizar la cantidad.');
          return;
        }
        valorEl.textContent = nuevaCantidad;
        const item = data.items.find(function (i) { return i.id == itemId; });
        if (item) subtotalEl.textContent = formateadorSoles.format(item.subtotal_centimos / 100);
        actualizarTotal(data);
      });
    }

    function mostrarError(mensaje) {
      errorEl.textContent = mensaje;
      errorEl.style.display = 'block';
    }

    function ocultarError() {
      errorEl.style.display = 'none';
    }
  });

  function actualizarTotal(data) {
    document.getElementById('carrito-total').textContent = formateadorSoles.format(data.subtotal_centimos / 100);
    actualizarBadgeCarrito(data.cantidad_total);
  }

  function mostrarCarritoVacio() {
    document.getElementById('carrito-contenido').style.display = 'none';
    document.getElementById('carrito-vacio').style.display = '';
  }

  function fetchJson(method, url, body) {
    return fetch(url, {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN,
      },
      body: body ? JSON.stringify(body) : null,
    }).then(function (r) { return r.json(); });
  }
</script>
@endpush

@endsection
