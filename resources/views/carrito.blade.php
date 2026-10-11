@extends('layouts.app')

@section('content')

<style>
  body.carrito-pagina { background: var(--enlix-bg-soft); }

  .carrito-container { max-width: 1120px; }
  .carrito-titulo { font-size: 24px; margin-bottom: 20px; }

  /* ---------- Tarjeta de productos disponibles ---------- */
  .carrito-card {
    background: #fff; border: 1px solid var(--enlix-border); border-radius: 10px;
    box-shadow: 0 2px 10px -4px rgba(10,37,64,0.08);
    overflow: hidden;
  }
  .carrito-card-header {
    display: flex; align-items: center; gap: 10px;
    padding: 16px 20px; border-bottom: 1px solid var(--enlix-border);
    font-size: 14px; font-weight: 500; color: var(--enlix-ink);
  }

  .carrito-fila {
    display: grid;
    grid-template-columns: 24px 64px 1fr auto auto 32px;
    align-items: center;
    gap: 14px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--enlix-border);
  }
  .carrito-fila:last-child { border-bottom: none; }

  .carrito-check, .carrito-check-todos {
    width: 20px; height: 20px; cursor: pointer; accent-color: var(--enlix-blue-800);
  }

  .carrito-fila-img {
    width: 64px; height: 64px; object-fit: contain; background: var(--enlix-bg-soft);
    border-radius: 8px; flex: none;
  }

  .carrito-fila-info { min-width: 0; }
  .carrito-fila-nombre {
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    margin: 0 0 4px; font-size: 14.5px; font-weight: 500; color: var(--enlix-ink); line-height: 1.35;
  }
  .carrito-fila-marca { margin: 0; font-size: 11px; font-weight: 600; letter-spacing: .04em; color: var(--enlix-muted); text-transform: uppercase; }

  .carrito-fila-precio { text-align: right; white-space: nowrap; }
  .carrito-fila-precio .precio-actual { display: block; font-size: 15px; font-weight: 700; color: var(--enlix-ink); }
  .carrito-fila-precio .precio-anterior { font-size: 12.5px; color: var(--enlix-muted); text-decoration: line-through; margin-right: 6px; }
  .badge-descuento {
    display: inline-block; margin-top: 3px; font-size: 11px; font-weight: 700; color: #fff;
    background: #d64545; border-radius: 4px; padding: 1px 6px;
  }

  .carrito-fila-cantidad { text-align: center; }
  .carrito-fila-stepper { display: flex; align-items: center; justify-content: center; gap: 8px; }
  .btn-cantidad-redondo {
    width: 30px; height: 30px; border-radius: 50%; border: none;
    background: var(--enlix-blue-900); color: #fff; font-size: 16px; line-height: 1;
    display: flex; align-items: center; justify-content: center;
  }
  .btn-cantidad-redondo:disabled { background: var(--enlix-border); color: var(--enlix-muted); }
  .cantidad-valor { min-width: 20px; text-align: center; font-weight: 600; font-size: 14px; }
  .cantidad-max { display: block; margin-top: 4px; font-size: 11px; color: var(--enlix-muted); }

  .carrito-fila-menu { position: relative; }
  .btn-menu {
    width: 32px; height: 32px; border: none; background: none; border-radius: 6px;
    font-size: 18px; line-height: 1; color: var(--enlix-muted); letter-spacing: 1px;
  }
  .btn-menu:hover { background: var(--enlix-bg-soft); }
  .menu-dropdown {
    position: absolute; right: 0; top: 36px; z-index: 20;
    background: #fff; border: 1px solid var(--enlix-border); border-radius: 8px;
    box-shadow: 0 12px 30px -10px rgba(10,37,64,0.25); min-width: 140px; overflow: hidden;
  }
  .menu-dropdown button {
    display: block; width: 100%; text-align: left; padding: 10px 14px; border: none; background: none;
    font-size: 13.5px; color: #b3261e;
  }
  .menu-dropdown button:hover { background: #fdeaea; }

  .carrito-fila-error { grid-column: 1 / -1; margin: 6px 0 0; font-size: 12.5px; color: #b3261e; }

  /* ---------- Fila deshabilitada mientras se guarda un cambio ---------- */
  .carrito-fila.guardando .carrito-fila-stepper button,
  .carrito-fila.guardando .carrito-check,
  .carrito-fila.guardando .btn-menu { opacity: .45; pointer-events: none; }

  /* ---------- Productos no disponibles ---------- */
  .carrito-no-disponibles { margin-top: 18px; border-radius: 10px; overflow: hidden; border: 1px solid #f2d98a; }
  .no-disponibles-header {
    display: flex; align-items: center; gap: 10px; width: 100%;
    padding: 14px 18px; background: #fdf6df; border: none;
    font-size: 14px; font-weight: 600; color: #7a5a00; text-align: left;
  }
  .no-disponibles-header .chevron { margin-left: auto; transition: transform .18s ease; }
  .no-disponibles-header[aria-expanded="false"] .chevron { transform: rotate(-90deg); }
  .no-disponibles-contenido { background: #fff; }
  .no-disponibles-contenido[hidden] { display: none; }

  .carrito-fila-gris {
    display: grid; grid-template-columns: 24px 64px 1fr auto; align-items: center; gap: 14px;
    padding: 16px 20px; border-bottom: 1px solid var(--enlix-border); color: var(--enlix-muted);
  }
  .carrito-fila-gris:last-child { border-bottom: none; }
  .carrito-fila-gris img { width: 64px; height: 64px; object-fit: contain; background: #f2f2f2; border-radius: 8px; filter: grayscale(1); opacity: .7; }
  .carrito-fila-gris .nombre { margin: 0 0 2px; font-size: 14px; font-weight: 500; }
  .carrito-fila-gris .estado { margin: 0; font-size: 12px; color: #b3261e; }
  .carrito-fila-gris .acciones { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; }
  .carrito-fila-gris .acciones a { font-size: 12.5px; }
  .carrito-fila-gris .acciones button { border: none; background: none; font-size: 12.5px; color: #b3261e; padding: 0; }

  /* ---------- Resumen ---------- */
  .carrito-resumen {
    background: #fff; border: 1px solid var(--enlix-border); border-radius: 10px;
    box-shadow: 0 2px 10px -4px rgba(10,37,64,0.08);
    padding: 22px; position: sticky; top: 96px;
  }
  .carrito-resumen h2 { font-size: 16px; margin: 0 0 16px; }
  .resumen-linea { display: flex; justify-content: space-between; align-items: center; font-size: 14.5px; color: var(--enlix-ink); padding: 7px 0; }
  .resumen-linea.descuento { color: #1f7a4d; }
  .resumen-linea-btn {
    display: flex; align-items: center; gap: 6px; border: none; background: none; padding: 0;
    font-size: 14.5px; color: #1f7a4d; font-weight: 500;
  }
  .resumen-linea-btn .chevron { transition: transform .18s ease; }
  .resumen-linea-btn[aria-expanded="false"] .chevron { transform: rotate(-90deg); }
  .detalle-descuentos { padding: 0 0 6px 4px; }
  .detalle-descuentos[hidden] { display: none; }
  .detalle-descuentos p { margin: 0; padding: 3px 0; font-size: 12.5px; color: var(--enlix-muted); display: flex; justify-content: space-between; gap: 10px; }
  .resumen-linea.total { font-size: 19px; font-weight: 700; margin-top: 8px; padding-top: 12px; border-top: 1px solid var(--enlix-border); }
  .resumen-igv-nota { margin: 2px 0 0; font-size: 11.5px; color: var(--enlix-muted); }

  .btn-continuar-compra:disabled { opacity: .5; cursor: not-allowed; }

  /* ---------- Barra fija inferior (móvil) ---------- */
  .carrito-barra-fija {
    display: none;
    position: fixed; inset-inline: 0; bottom: 0; z-index: 1070;
    align-items: center; justify-content: space-between; gap: 14px;
    background: #fff; border-top: 1px solid var(--enlix-border);
    padding: 10px 16px calc(10px + env(safe-area-inset-bottom));
    box-shadow: 0 -8px 24px -12px rgba(10,37,64,0.25);
  }
  .carrito-barra-fija .total { line-height: 1.2; }
  .carrito-barra-fija .total span { display: block; font-size: 11.5px; color: var(--enlix-muted); }
  .carrito-barra-fija .total strong { font-size: 18px; color: var(--enlix-ink); }
  .carrito-barra-fija .btn-continuar-compra { flex: none; padding-left: 22px; padding-right: 22px; }

  @media (max-width: 991.98px) {
    .carrito-fila {
      grid-template-columns: 24px 64px 1fr 32px;
      grid-template-areas: "check img nombre menu" "precio precio cantidad cantidad";
      row-gap: 10px;
    }
    .carrito-check { grid-area: check; }
    .carrito-fila-img { grid-area: img; }
    .carrito-fila-info { grid-area: nombre; }
    .carrito-fila-menu { grid-area: menu; justify-self: end; }
    .carrito-fila-precio { grid-area: precio; text-align: left; }
    .carrito-fila-cantidad { grid-area: cantidad; text-align: right; }
    .carrito-fila-stepper { justify-content: flex-end; }

    .carrito-resumen { position: static; margin-bottom: 90px; }

    .carrito-barra-fija { display: flex; }
    body.carrito-con-barra-fija .wa-float { bottom: 86px; }
  }

  /* Áreas táctiles >= 44px y sin zoom automático de iOS en inputs (no hay
     inputs de texto en esta página, pero sí botones/checkboxes). */
  @media (max-width: 991.98px) {
    .btn-cantidad-redondo, .btn-menu, .carrito-check, .carrito-check-todos { min-width: 44px; min-height: 44px; }
    .carrito-fila-stepper .btn-cantidad-redondo { width: 44px; height: 44px; }
  }

  /* ---------- Estado vacío ---------- */
  .carrito-vacio { text-align: center; padding: 70px 20px; }
</style>

<section class="section-pad">
  <div class="container carrito-container">

    @if ($resumen['items']->isEmpty())
      <div class="carrito-vacio">
        <p style="color: var(--enlix-muted); margin-bottom: 16px;">Tu carrito está vacío.</p>
        <a href="{{ route('productos') }}" class="btn btn-primary-enlix">Ver productos</a>
      </div>
    @else
      @php
        $disponibles = $resumen['items']->where('disponible', true)->values();
        $noDisponibles = $resumen['items']->where('disponible', false)->values();
      @endphp

      <h1 class="carrito-titulo" id="carrito-titulo">Carrito (<span id="carrito-titulo-cantidad">{{ $resumen['cantidad_total'] }}</span> productos)</h1>

      <div class="row g-4">
        <div class="col-lg-8">

          <div class="carrito-card">
            <div class="carrito-card-header">
              <input type="checkbox" class="carrito-check-todos" id="chk-todos" checked aria-label="Seleccionar todos los productos">
              <label for="chk-todos">Seleccionar todos</label>
            </div>

            <div id="lista-disponibles">
              @foreach ($disponibles as $item)
                <div
                  class="carrito-fila"
                  data-item-id="{{ $item['id'] }}"
                  data-stock="{{ $item['stock_disponible'] }}"
                  data-precio-unitario-centimos="{{ $item['precio_unitario_centimos'] }}"
                  data-precio-comparacion-centimos="{{ $item['precio_comparacion_centimos'] ?? '' }}"
                >
                  <input type="checkbox" class="carrito-check" checked aria-label="Incluir {{ $item['nombre'] }} en la compra">

                  <img src="{{ $item['imagen_url'] }}" alt="" class="carrito-fila-img" loading="lazy">

                  <div class="carrito-fila-info">
                    <p class="carrito-fila-nombre">{{ $item['nombre'] }}</p>
                    @if ($item['marca_o_categoria'])
                      <p class="carrito-fila-marca">{{ strtoupper($item['marca_o_categoria']) }}</p>
                    @endif
                  </div>

                  <div class="carrito-fila-precio">
                    @if ($item['precio_comparacion_centimos'] && $item['precio_comparacion_centimos'] > $item['precio_unitario_centimos'])
                      <span class="precio-anterior">S/ {{ number_format($item['precio_comparacion_centimos'] / 100, 2) }}</span>
                    @endif
                    <span class="precio-actual">S/ {{ number_format($item['precio_unitario_centimos'] / 100, 2) }}</span>
                    @if ($item['precio_comparacion_centimos'] && $item['precio_comparacion_centimos'] > $item['precio_unitario_centimos'])
                      <span class="badge-descuento">-{{ round((1 - $item['precio_unitario_centimos'] / $item['precio_comparacion_centimos']) * 100) }}%</span>
                    @endif
                  </div>

                  <div class="carrito-fila-cantidad">
                    <div class="carrito-fila-stepper">
                      <button type="button" class="btn-cantidad-redondo" data-accion="restar" aria-label="Restar una unidad de {{ $item['nombre'] }}">−</button>
                      <span class="cantidad-valor" aria-live="polite">{{ $item['cantidad'] }}</span>
                      <button type="button" class="btn-cantidad-redondo" data-accion="sumar" aria-label="Agregar una unidad de {{ $item['nombre'] }}">+</button>
                    </div>
                    <span class="cantidad-max">Máx {{ $item['stock_disponible'] }} unidades</span>
                  </div>

                  <div class="carrito-fila-menu">
                    <button type="button" class="btn-menu" data-menu-toggle aria-haspopup="true" aria-expanded="false" aria-label="Más opciones de {{ $item['nombre'] }}">⋮</button>
                    <div class="menu-dropdown" data-menu hidden>
                      <button type="button" data-accion="eliminar">Eliminar</button>
                    </div>
                  </div>

                  <p class="carrito-fila-error" hidden></p>
                </div>
              @endforeach
            </div>
          </div>

          @if ($noDisponibles->isNotEmpty())
            <div class="carrito-no-disponibles">
              <button type="button" class="no-disponibles-header" id="toggle-no-disponibles" aria-expanded="true" aria-controls="no-disponibles-contenido">
                <span aria-hidden="true">⚠️</span>
                Productos no disponibles ({{ $noDisponibles->count() }})
                <span class="chevron" aria-hidden="true">▾</span>
              </button>
              <div class="no-disponibles-contenido" id="no-disponibles-contenido">
                @foreach ($noDisponibles as $item)
                  <div class="carrito-fila-gris" data-item-id="{{ $item['id'] }}">
                    <input type="checkbox" disabled aria-label="{{ $item['nombre'] }} no disponible">
                    <img src="{{ $item['imagen_url'] }}" alt="">
                    <div>
                      <p class="nombre">{{ $item['nombre'] }}</p>
                      <p class="estado">{{ $item['stock_disponible'] <= 0 ? 'Agotado' : 'Ya no disponible' }}</p>
                    </div>
                    <div class="acciones">
                      <a href="{{ route('productos') }}{{ $item['categoria_slug'] ? '?categoria='.$item['categoria_slug'] : '' }}">Ver productos similares</a>
                      <button type="button" data-accion="eliminar-no-disponible">Eliminar</button>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          @endif

        </div>

        <div class="col-lg-4">
          <div class="carrito-resumen" id="carrito-resumen">
            <h2>Resumen de la orden</h2>

            <div class="resumen-linea">
              <span>Productos (<span id="resumen-n-productos">0</span>)</span>
              <span id="resumen-productos-monto">S/ 0.00</span>
            </div>

            <div class="resumen-linea descuento" id="linea-descuento" hidden>
              <button type="button" class="resumen-linea-btn" id="toggle-descuentos" aria-expanded="false" aria-controls="detalle-descuentos">
                Descuentos (<span id="resumen-n-descuentos">0</span>)
                <span class="chevron" aria-hidden="true">▾</span>
              </button>
              <span id="resumen-descuento-monto">-S/ 0.00</span>
            </div>
            <div class="detalle-descuentos" id="detalle-descuentos" hidden></div>

            <div class="resumen-linea total">
              <span>Total</span>
              <span id="resumen-total">S/ 0.00</span>
            </div>
            <p class="resumen-igv-nota">Precio incluye IGV.</p>

            <button type="button" class="btn btn-primary-enlix w-100 mt-3 btn-continuar-compra" id="btn-continuar">Continuar compra</button>
          </div>
        </div>
      </div>

      <div class="carrito-barra-fija">
        <div class="total">
          <span>Total</span>
          <strong id="barra-fija-total">S/ 0.00</strong>
        </div>
        <button type="button" class="btn btn-primary-enlix btn-continuar-compra" id="btn-continuar-movil">Continuar compra</button>
      </div>
    @endif

  </div>
</section>

@if ($resumen['items']->isNotEmpty())
@push('scripts')
<script nonce="{{ $cspNonce }}">
  const CSRF_TOKEN = @json(csrf_token());
  const URL_ITEMS = @json(route('carrito.items.store'));
  const URL_CHECKOUT = @json(route('checkout.index'));
  const formateadorSoles = new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' });

  document.body.classList.add('carrito-pagina');

  const filasDisponibles = Array.prototype.slice.call(document.querySelectorAll('#lista-disponibles .carrito-fila'));
  const chkTodos = document.getElementById('chk-todos');
  const seleccionados = new Set(filasDisponibles.map(function (f) { return f.dataset.itemId; }));

  if (filasDisponibles.length > 0) {
    document.body.classList.add('carrito-con-barra-fija');
  }

  // ---------- Selección (checkboxes) ----------
  filasDisponibles.forEach(function (fila) {
    const chk = fila.querySelector('.carrito-check');
    chk.addEventListener('change', function () {
      if (chk.checked) seleccionados.add(fila.dataset.itemId);
      else seleccionados.delete(fila.dataset.itemId);
      sincronizarCheckTodos();
      recalcularResumen();
    });
  });

  chkTodos.addEventListener('change', function () {
    filasDisponibles.forEach(function (fila) {
      fila.querySelector('.carrito-check').checked = chkTodos.checked;
      if (chkTodos.checked) seleccionados.add(fila.dataset.itemId);
      else seleccionados.delete(fila.dataset.itemId);
    });
    recalcularResumen();
  });

  function sincronizarCheckTodos() {
    const total = filasDisponibles.length;
    const marcados = seleccionados.size;
    chkTodos.checked = marcados === total;
    chkTodos.indeterminate = marcados > 0 && marcados < total;
  }

  // ---------- Cantidad (+/-) ----------
  filasDisponibles.forEach(function (fila) {
    const itemId = fila.dataset.itemId;
    const stock = parseInt(fila.dataset.stock, 10);
    const valorEl = fila.querySelector('.cantidad-valor');
    const btnRestar = fila.querySelector('[data-accion="restar"]');
    const btnSumar = fila.querySelector('[data-accion="sumar"]');
    const errorEl = fila.querySelector('.carrito-fila-error');

    pintarLimiteCantidad(fila, stock);

    btnRestar.addEventListener('click', function () {
      const actual = parseInt(valorEl.textContent, 10);
      if (actual <= 1) return;
      actualizarCantidad(fila, itemId, actual, actual - 1, valorEl, errorEl, stock);
    });

    btnSumar.addEventListener('click', function () {
      const actual = parseInt(valorEl.textContent, 10);
      if (actual >= stock) return;
      actualizarCantidad(fila, itemId, actual, actual + 1, valorEl, errorEl, stock);
    });
  });

  function pintarLimiteCantidad(fila, stock) {
    const actual = parseInt(fila.querySelector('.cantidad-valor').textContent, 10);
    fila.querySelector('[data-accion="restar"]').disabled = actual <= 1;
    fila.querySelector('[data-accion="sumar"]').disabled = actual >= stock;
  }

  function actualizarCantidad(fila, itemId, cantidadAnterior, nuevaCantidad, valorEl, errorEl, stock) {
    errorEl.hidden = true;
    fila.classList.add('guardando');

    fetchJsonEnlix('PATCH', URL_ITEMS + '/' + itemId, { cantidad: nuevaCantidad }, CSRF_TOKEN)
      .then(function (data) {
        if (!data.ok) {
          errorEl.textContent = data.mensaje || 'No se pudo actualizar la cantidad.';
          errorEl.hidden = false;
          return;
        }

        valorEl.textContent = nuevaCantidad;
        fila.dataset.precioUnitarioCentimos = (data.items.find(function (i) { return i.id == itemId; }) || {}).precio_unitario_centimos || fila.dataset.precioUnitarioCentimos;
        pintarLimiteCantidad(fila, stock);
        actualizarBadgeCarrito(data.cantidad_total);
        actualizarTituloCantidad();
        recalcularResumen();
      })
      .catch(function () {
        valorEl.textContent = cantidadAnterior;
        errorEl.textContent = 'No se pudo confirmar el cambio. Intenta de nuevo.';
        errorEl.hidden = false;
      })
      .then(function () {
        fila.classList.remove('guardando');
      });
  }

  // ---------- Menú ⋮ (eliminar con confirmación) ----------
  document.addEventListener('click', function (e) {
    const toggle = e.target.closest('[data-menu-toggle]');
    document.querySelectorAll('.menu-dropdown:not([hidden])').forEach(function (m) {
      if (!toggle || m !== toggle.parentElement.querySelector('[data-menu]')) {
        m.hidden = true;
        const btn = m.parentElement.querySelector('[data-menu-toggle]');
        if (btn) btn.setAttribute('aria-expanded', 'false');
      }
    });

    if (toggle) {
      const menu = toggle.parentElement.querySelector('[data-menu]');
      const abierto = !menu.hidden;
      menu.hidden = abierto;
      toggle.setAttribute('aria-expanded', abierto ? 'false' : 'true');
    }
  });

  filasDisponibles.forEach(function (fila) {
    const itemId = fila.dataset.itemId;
    const nombre = fila.querySelector('.carrito-fila-nombre').textContent.trim();

    fila.querySelector('[data-accion="eliminar"]').addEventListener('click', function () {
      if (!confirm('¿Eliminar "' + nombre + '" del carrito?')) return;
      eliminarItem(itemId, fila);
    });
  });

  document.querySelectorAll('[data-accion="eliminar-no-disponible"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const fila = btn.closest('.carrito-fila-gris');
      const nombre = fila.querySelector('.nombre').textContent.trim();
      if (!confirm('¿Eliminar "' + nombre + '" del carrito?')) return;
      eliminarItem(fila.dataset.itemId, fila);
    });
  });

  function eliminarItem(itemId, fila) {
    fetchJsonEnlix('DELETE', URL_ITEMS + '/' + itemId, null, CSRF_TOKEN).then(function (data) {
      if (!data.ok) return;

      seleccionados.delete(itemId);
      // fila puede venir de filasDisponibles (item disponible) o ser una
      // .carrito-fila-gris suelta (no disponible): solo la primera vive en
      // el array que recorren recalcularResumen()/actualizarTituloCantidad(),
      // asi que hay que sacarla de ahi tambien o quedaria contada "fantasma"
      // aunque ya no este en el DOM.
      const idx = filasDisponibles.indexOf(fila);
      if (idx !== -1) filasDisponibles.splice(idx, 1);

      fila.remove();
      actualizarBadgeCarrito(data.cantidad_total);
      actualizarTituloCantidad();
      recalcularResumen();

      if (filasDisponibles.length === 0 && document.querySelectorAll('.carrito-fila-gris').length === 0) {
        window.location.reload();
      }
    }).catch(function () {
      alert('No se pudo eliminar. Intenta de nuevo.');
    });
  }

  // ---------- Sección "no disponibles" colapsable ----------
  const toggleNoDisponibles = document.getElementById('toggle-no-disponibles');
  if (toggleNoDisponibles) {
    toggleNoDisponibles.addEventListener('click', function () {
      const contenido = document.getElementById('no-disponibles-contenido');
      const abierto = toggleNoDisponibles.getAttribute('aria-expanded') === 'true';
      toggleNoDisponibles.setAttribute('aria-expanded', abierto ? 'false' : 'true');
      contenido.hidden = abierto;
    });
  }

  // ---------- Detalle de descuentos (desplegable) ----------
  const toggleDescuentos = document.getElementById('toggle-descuentos');
  toggleDescuentos.addEventListener('click', function () {
    const detalle = document.getElementById('detalle-descuentos');
    const abierto = toggleDescuentos.getAttribute('aria-expanded') === 'true';
    toggleDescuentos.setAttribute('aria-expanded', abierto ? 'false' : 'true');
    detalle.hidden = abierto;
  });

  // ---------- Título "Carrito (N productos)": total del carrito, no depende
  // de la selección (igual criterio que el badge del header). ----------
  function actualizarTituloCantidad() {
    const total = filasDisponibles.reduce(function (acc, fila) {
      return acc + parseInt(fila.querySelector('.cantidad-valor').textContent, 10);
    }, 0);
    const el = document.getElementById('carrito-titulo-cantidad');
    if (el) el.textContent = total;
  }

  // ---------- Resumen ----------
  function recalcularResumen() {
    let cantidadProductos = 0;
    let montoProductos = 0;
    let montoDescuento = 0;
    let itemsConDescuento = [];

    filasDisponibles.forEach(function (fila) {
      if (!seleccionados.has(fila.dataset.itemId)) return;

      const cantidad = parseInt(fila.querySelector('.cantidad-valor').textContent, 10);
      const precioUnitario = parseInt(fila.dataset.precioUnitarioCentimos, 10);
      const precioComparacion = parseInt(fila.dataset.precioComparacionCentimos, 10) || 0;
      const precioLista = precioComparacion > precioUnitario ? precioComparacion : precioUnitario;

      cantidadProductos += cantidad;
      montoProductos += precioLista * cantidad;

      if (precioComparacion > precioUnitario) {
        const ahorro = (precioComparacion - precioUnitario) * cantidad;
        montoDescuento += ahorro;
        itemsConDescuento.push({
          nombre: fila.querySelector('.carrito-fila-nombre').textContent.trim(),
          ahorro: ahorro,
        });
      }
    });

    const total = montoProductos - montoDescuento;

    document.getElementById('resumen-n-productos').textContent = cantidadProductos;
    document.getElementById('resumen-productos-monto').textContent = formateadorSoles.format(montoProductos / 100);
    document.getElementById('resumen-total').textContent = formateadorSoles.format(total / 100);
    document.getElementById('barra-fija-total').textContent = formateadorSoles.format(total / 100);

    const lineaDescuento = document.getElementById('linea-descuento');
    if (montoDescuento > 0) {
      lineaDescuento.hidden = false;
      document.getElementById('resumen-n-descuentos').textContent = itemsConDescuento.length;
      document.getElementById('resumen-descuento-monto').textContent = '-' + formateadorSoles.format(montoDescuento / 100);

      const detalle = document.getElementById('detalle-descuentos');
      detalle.innerHTML = '';
      itemsConDescuento.forEach(function (it) {
        const p = document.createElement('p');
        const nombre = document.createElement('span');
        nombre.textContent = it.nombre;
        const ahorro = document.createElement('span');
        ahorro.textContent = '-' + formateadorSoles.format(it.ahorro / 100);
        p.appendChild(nombre);
        p.appendChild(ahorro);
        detalle.appendChild(p);
      });
    } else {
      lineaDescuento.hidden = true;
      document.getElementById('detalle-descuentos').hidden = true;
      toggleDescuentos.setAttribute('aria-expanded', 'false');
    }

    const idsSeleccionados = Array.from(seleccionados).join(',');
    const urlContinuar = URL_CHECKOUT + (idsSeleccionados ? '?items=' + idsSeleccionados : '');
    document.querySelectorAll('.btn-continuar-compra').forEach(function (btn) {
      btn.disabled = seleccionados.size === 0;
      btn.onclick = function () {
        if (!btn.disabled) window.location.href = urlContinuar;
      };
    });
  }

  recalcularResumen();
</script>
@endpush
@endif

@endsection
