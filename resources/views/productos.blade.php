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
  #pago-resultado {
    max-width: 640px; margin: 0 auto; display: none; border-radius: 6px; padding: 16px 20px;
  }
  #pago-resultado.ok    { display: block; background: #e8f7ee; color: #176b3a; border: 1px solid #aadcbf; }
  #pago-resultado.error { display: block; background: #fdeaea; color: #9b1c1c; border: 1px solid #f2b8b8; }
  #pago-resultado.info  { display: block; background: #eef4fd; color: #1e4e8c; border: 1px solid #bcd4f2; }
</style>

<section class="section-pad">
  <div class="container">

    <div class="text-center mb-5">
      <p class="svc-block-num">Tienda</p>
      <h1>Nuestros productos</h1>
      <p style="color: var(--enlix-muted); max-width: 640px; margin: 0 auto;">
        Elige el plan que mejor se adapte a tu empresa y págalo de forma segura.
      </p>
    </div>

    <div id="pago-resultado" class="mb-4"></div>

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

            <button type="button"
                    class="btn btn-primary-enlix w-100 btn-comprar"
                    data-slug="{{ $slug }}"
                    data-nombre="{{ $p['nombre'] }}"
                    data-monto="{{ $p['precio_centimos'] }}">
              Comprar ahora
            </button>
          </div>
        </div>
      @endforeach
    </div>

    <p class="text-center mt-4" style="color: var(--enlix-muted); font-size: 13px;">
      Pagos procesados de forma segura por Culqi. No almacenamos los datos de tu tarjeta.
    </p>

  </div>
</section>

{{-- Modal: datos del comprador (requeridos por la Orders API de Culqi) --}}
<div class="modal fade" id="modalDatos" tabindex="-1" aria-labelledby="modalDatosLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalDatosLabel">Datos de compra</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p id="modalProducto" class="mb-3" style="color: var(--enlix-muted); font-size: 14px;"></p>
        <div class="row g-3">
          <div class="col-sm-6">
            <label class="form-label">Nombre</label>
            <input type="text" class="form-control" id="f_first_name" autocomplete="given-name">
          </div>
          <div class="col-sm-6">
            <label class="form-label">Apellido</label>
            <input type="text" class="form-control" id="f_last_name" autocomplete="family-name">
          </div>
          <div class="col-12">
            <label class="form-label">Correo electrónico</label>
            <input type="email" class="form-control" id="f_email" autocomplete="email">
          </div>
          <div class="col-12">
            <label class="form-label">Teléfono</label>
            <input type="text" class="form-control" id="f_phone" placeholder="+51 999 999 999" autocomplete="tel">
          </div>
        </div>
        <div id="modal-error" class="text-danger mt-3" style="font-size: 13px; display: none;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary-enlix w-100" id="btnContinuar">Continuar al pago</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
{{-- Culqi Checkout V4 --}}
<script src="https://checkout.culqi.com/js/v4"></script>
<script>
  const CULQI_PUBLIC_KEY = @json($culqi_pk);
  const CSRF_TOKEN       = @json(csrf_token());
  const URLS = {
    orden:     @json(route('checkout.orden')),
    verificar: @json(route('checkout.verificar')),
    pagar:     @json(route('checkout.pagar')),
  };

  let productoActual = null;
  const modalDatos = new bootstrap.Modal(document.getElementById('modalDatos'));

  if (CULQI_PUBLIC_KEY) {
    Culqi.publicKey = CULQI_PUBLIC_KEY;
  }

  // 1) Click en "Comprar" -> abrir el modal de datos.
  document.querySelectorAll('.btn-comprar').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!CULQI_PUBLIC_KEY) {
        mostrarResultado('error', 'Falta configurar la llave pública de Culqi (CULQI_PUBLIC_KEY).');
        return;
      }
      productoActual = {
        slug:   btn.dataset.slug,
        nombre: btn.dataset.nombre,
        monto:  parseInt(btn.dataset.monto, 10),
      };
      document.getElementById('modalProducto').textContent =
        productoActual.nombre + ' — S/ ' + (productoActual.monto / 100).toFixed(2);
      ocultarErrorModal();
      modalDatos.show();
    });
  });

  // 2) Continuar -> crear la orden en el backend y abrir el checkout.
  document.getElementById('btnContinuar').addEventListener('click', function () {
    const first_name = document.getElementById('f_first_name').value.trim();
    const last_name  = document.getElementById('f_last_name').value.trim();
    const email      = document.getElementById('f_email').value.trim();
    const phone      = document.getElementById('f_phone').value.trim();

    if (!first_name || !last_name || !email || !phone) {
      return mostrarErrorModal('Completa todos los campos para continuar.');
    }

    Object.assign(productoActual, { first_name, last_name, email, phone_number: phone });

    postJson(URLS.orden, {
      producto:     productoActual.slug,
      first_name:   first_name,
      last_name:    last_name,
      email:        email,
      phone_number: phone,
    }).then(function (data) {
      if (!data.ok) {
        return mostrarErrorModal(data.mensaje || 'No se pudo iniciar el pago.');
      }
      modalDatos.hide();
      abrirCheckout(data.order_id);
    }).catch(function () {
      mostrarErrorModal('Error de conexión. Intenta nuevamente.');
    });
  });

  function abrirCheckout(orderId) {
    Culqi.settings({
      title:    'Enlix',
      currency: 'PEN',
      amount:   productoActual.monto,
      order:    orderId,
    });

    Culqi.options({
      lang: 'auto',
      installments: false,
      paymentMethods: {
        tarjeta:    true,
        yape:       true,
        bancaMovil: true,
        billetera:  true,
        cuotealo:   true,
        agente:     true,   // Agentes y bodegas (PagoEfectivo): confirmado por webhook
      },
    });

    Culqi.open();
  }

  // 3) Callback global que Culqi invoca tras el pago.
  function culqi() {
    if (Culqi.token) {
      // Tarjeta / Yape -> cargo con token.
      postJson(URLS.pagar, {
        token:    Culqi.token.id,
        producto: productoActual.slug,
        email:    productoActual.email,
      }).then(resolverResultado).catch(errorConexion);
    } else if (Culqi.order) {
      // Billetera / banca móvil / Cuotéalo -> verificar la orden.
      postJson(URLS.verificar, {
        order_id: Culqi.order.id,
        producto: productoActual.slug,
      }).then(resolverResultado).catch(errorConexion);
    } else if (Culqi.error) {
      mostrarResultado('error', Culqi.error.user_message || 'No se pudo procesar el pago.');
    }
  }

  function resolverResultado(data) {
    if (data.ok) {
      mostrarResultado('ok', data.mensaje + (data.charge_id ? ' (ID: ' + data.charge_id + ')' : ''));
    } else if (data.pendiente) {
      mostrarResultado('info', data.mensaje);
    } else {
      mostrarResultado('error', data.mensaje || 'No se pudo procesar el pago.');
    }
  }

  function errorConexion() {
    mostrarResultado('error', 'Error de conexión al procesar el pago.');
  }

  function postJson(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN,
      },
      body: JSON.stringify(body),
    }).then(function (r) { return r.json(); });
  }

  function mostrarResultado(tipo, mensaje) {
    const icono = { ok: '✅ ', error: '❌ ', info: 'ℹ️ ' }[tipo] || '';
    const box = document.getElementById('pago-resultado');
    box.className = 'mb-4 ' + tipo;
    box.textContent = icono + mensaje;
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function mostrarErrorModal(mensaje) {
    const e = document.getElementById('modal-error');
    e.textContent = mensaje;
    e.style.display = 'block';
  }

  function ocultarErrorModal() {
    document.getElementById('modal-error').style.display = 'none';
  }
</script>
@endpush

@endsection
