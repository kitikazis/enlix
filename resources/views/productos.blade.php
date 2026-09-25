@extends('layouts.app')

@section('content')

<style>
  .prod-card {
    background: #fff;
    border: 1px solid var(--enlix-border);
    border-radius: 8px;
    padding: 28px;
    display: flex;
    flex-direction: column;
    height: 100%;
    transition: box-shadow .2s ease, transform .2s ease;
  }
  .prod-card:hover {
    box-shadow: 0 12px 32px rgba(0, 0, 0, .08);
    transform: translateY(-3px);
  }
  .prod-card-title { font-size: 20px; margin: 0 0 8px; color: var(--enlix-ink); }
  .prod-card-desc  { color: var(--enlix-muted); font-size: 14px; line-height: 1.6; margin: 0; }
  .prod-card-list  { list-style: none; padding: 0; margin: 16px 0 20px; }
  .prod-card-list li {
    font-size: 14px; padding: 6px 0 6px 24px; position: relative; color: var(--enlix-ink);
  }
  .prod-card-list li::before {
    content: '✓'; position: absolute; left: 0; color: var(--enlix-blue-900); font-weight: 700;
  }
  .prod-card-price { font-size: 34px; font-weight: 700; color: var(--enlix-ink); margin: auto 0 18px; }
  .prod-card-price .currency { font-size: 18px; color: var(--enlix-muted); vertical-align: super; }
  .prod-card-error { font-size: 13px; }
</style>

<section class="section-pad">
  <div class="container">

    <div class="text-center mb-5">
      <p class="svc-block-num">Tienda</p>
      <h1>Nuestros productos</h1>
      <p style="color: var(--enlix-muted); max-width: 640px; margin: 0 auto;">
        Explora nuestro catálogo y agrega lo que necesites a tu carrito.
      </p>
    </div>

    <div class="row g-4 justify-content-center">
      @foreach ($productos as $slug => $p)
        <div class="col-md-6 col-lg-4">
          <div class="prod-card">
            <h3 class="prod-card-title">{{ $p['nombre'] }}</h3>
            <p class="prod-card-desc">{{ $p['descripcion'] }}</p>

            @if (! empty($p['features']))
              <ul class="prod-card-list">
                @foreach ($p['features'] as $f)
                  <li>{{ $f }}</li>
                @endforeach
              </ul>
            @endif

            <div class="prod-card-price">
              <span class="currency">S/</span> {{ number_format($p['precio_centimos'] / 100, 2) }}
            </div>

            @if ($p['stock_disponible'] > 0)
              <button type="button"
                      class="btn btn-primary-enlix w-100 btn-agregar-carrito"
                      data-producto-id="{{ $p['id'] }}">
                Agregar al carrito
              </button>
            @else
              <button type="button" class="btn btn-primary-enlix w-100" disabled>Agotado</button>
            @endif
          </div>
        </div>
      @endforeach
    </div>

  </div>
</section>

@push('scripts')
<script nonce="{{ $cspNonce }}">
  const CSRF_TOKEN = @json(csrf_token());
  const URL_AGREGAR_CARRITO = @json(route('carrito.items.store'));
  const URL_RESUMEN_CARRITO = @json(route('carrito.resumen'));

  document.querySelectorAll('.btn-agregar-carrito').forEach(function (btn) {
    const textoOriginal = btn.textContent.trim();

    btn.addEventListener('click', function () {
      if (btn.disabled) return;

      btn.disabled = true;
      ocultarErrorCarrito(btn);

      fetchJsonEnlix('POST', URL_AGREGAR_CARRITO, {
        producto_id: parseInt(btn.dataset.productoId, 10),
        cantidad: 1,
      }, CSRF_TOKEN).then(function (data) {
        if (!data.ok) {
          mostrarErrorCarrito(btn, data.mensaje || 'No se pudo agregar al carrito.');
          btn.disabled = false;
          return;
        }

        actualizarBadgeCarrito(data.cantidad_total);

        // Feedback de "agregado" durante 1.5s, como pide el diseño.
        btn.textContent = '✓ Agregado';
        setTimeout(function () {
          btn.textContent = textoOriginal;
          btn.disabled = false;
        }, 1500);
      }).catch(function () {
        // La petición pudo haber llegado bien al servidor aunque la
        // respuesta no se pudo leer como JSON (ver fetchJsonEnlix en
        // enlix.js) - se refresca el contador real en vez de asumir que no
        // se agregó nada.
        refrescarBadgeCarrito();
        mostrarErrorCarrito(btn, 'No se pudo confirmar el agregado. Revisa tu carrito antes de reintentar.');
        btn.disabled = false;
      });
    });
  });

  function refrescarBadgeCarrito() {
    fetchJsonEnlix('GET', URL_RESUMEN_CARRITO, null, CSRF_TOKEN)
      .then(function (data) { actualizarBadgeCarrito(data.cantidad_total); })
      .catch(function () {});
  }

  function mostrarErrorCarrito(btn, mensaje) {
    const card = btn.closest('.prod-card');
    let error = card.querySelector('.prod-card-error');
    if (!error) {
      error = document.createElement('p');
      error.className = 'prod-card-error text-danger mt-2 mb-0';
      card.appendChild(error);
    }
    error.textContent = mensaje;
  }

  function ocultarErrorCarrito(btn) {
    const error = btn.closest('.prod-card').querySelector('.prod-card-error');
    if (error) error.remove();
  }
</script>
@endpush

@endsection
