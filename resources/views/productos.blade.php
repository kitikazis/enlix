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
    align-items: center; gap: 12px;
  }
  #pago-resultado.ok        { display: flex; background: #e8f7ee; color: #176b3a; border: 1px solid #aadcbf; animation: pagoResultadoIn .35s ease; }
  #pago-resultado.error     { display: flex; background: #fdeaea; color: #9b1c1c; border: 1px solid #f2b8b8; animation: pagoResultadoIn .35s ease; }
  #pago-resultado.info,
  #pago-resultado.pendiente { display: flex; background: #eef4fd; color: #1e4e8c; border: 1px solid #bcd4f2; animation: pagoResultadoIn .35s ease; }

  .pago-resultado-icono {
    display: inline-flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; border-radius: 50%; flex: none;
    font-size: 14px; font-weight: 700; color: #fff; line-height: 1;
    animation: pagoResultadoIconoIn .4s cubic-bezier(.34, 1.56, .64, 1) .1s both;
  }
  .pago-resultado-icono.ok    { background: #1fae5c; }
  .pago-resultado-icono.error { background: #d64545; }
  .pago-resultado-icono.info  { background: #3d7fd1; }

  /* Estado "pendiente": el pago sigue confirmándose (esperando el IPN de
     Izipay), así que en vez del icono estático usamos un spinner para
     comunicar "en curso" en vez de "resultado final". */
  .pago-resultado-spinner {
    width: 20px; height: 20px; border-radius: 50%; flex: none;
    border: 3px solid rgba(30, 78, 140, .2);
    border-top-color: #1e4e8c;
    animation: pagoResultadoSpin .8s linear infinite;
  }

  @keyframes pagoResultadoIn {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  @keyframes pagoResultadoIconoIn {
    from { transform: scale(0); }
    to   { transform: scale(1); }
  }
  @keyframes pagoResultadoSpin {
    to { transform: rotate(360deg); }
  }

  /* Centra el contenido de Krypton con flex en el WRAPPER (contenedor
     externo), no en el elemento de Krypton mismo. flex en el padre no
     afecta el "position: fixed" que Krypton aplica al abrir el popin -
     ese tipo de posicionamiento ignora el layout del padre. */
  #izipay-popin-wrapper {
    display: flex;
    justify-content: center;
  }
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
      Pagos procesados de forma segura por Izipay. No almacenamos los datos de tu tarjeta.
    </p>

  </div>
</section>

{{-- Modal: datos del comprador (requeridos por Izipay para crear el formToken) --}}
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
            <input type="text" class="form-control" id="f_first_name" autocomplete="given-name" value="{{ $autollenar_test ? 'Juan' : '' }}">
          </div>
          <div class="col-sm-6">
            <label class="form-label">Apellido</label>
            <input type="text" class="form-control" id="f_last_name" autocomplete="family-name" value="{{ $autollenar_test ? 'Perez' : '' }}">
          </div>
          <div class="col-12">
            <label class="form-label">Correo electrónico</label>
            <input type="email" class="form-control" id="f_email" autocomplete="email" value="{{ $autollenar_test ? 'juan.perez@example.com' : '' }}">
          </div>
          <div class="col-12">
            <label class="form-label">Teléfono</label>
            <input type="text" class="form-control" id="f_phone" placeholder="+51 999 999 999" autocomplete="tel" value="{{ $autollenar_test ? '+51999999999' : '' }}">
          </div>
          <div class="col-12">
            <label class="form-label">Documento de identidad (DNI)</label>
            <input type="text" class="form-control" id="f_dni" placeholder="12345678" maxlength="15" autocomplete="off" value="{{ $autollenar_test ? '12345678' : '' }}">
            <div class="form-text">Necesario para habilitar Yape, Plin y otros medios de pago además de tarjeta.</div>
          </div>
        </div>
        @if ($autollenar_test)
          <p class="text-warning mt-2 mb-0" style="font-size: 12px;">⚠ Datos de prueba precargados (IZIPAY_AUTOLLENAR_TEST=true). Apágalo en el .env antes de recibir clientes reales.</p>
        @endif
        <div id="modal-error" class="text-danger mt-3" style="font-size: 13px; display: none;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary-enlix w-100" id="btnContinuar">Continuar al pago</button>
      </div>
    </div>
  </div>
</div>

{{-- Contenedor del PopIn de Izipay (cliente Krypton). El formToken se asigna
     dinámicamente con KR.setFormToken() tras crearlo en el backend. Los
     campos y el boton deben existir como hijos reales en el HTML (asi lo
     exige Krypton, ver ejemplos oficiales lyra/rest-php-examples e
     izipay-pe/Popin-PaymentForm-Php-Sdk): sin ellos, "kr-payment-button"
     no existe en el DOM y el popin nunca se abre. Krypton oculta este
     bloque por su cuenta hasta que se abre el popin; no forzar display:none
     aqui porque interfiere con esa logica interna. --}}
<div id="izipay-popin-wrapper">
  <div class="kr-embedded" kr-popin id="izipay-popin">
    <div class="kr-pan"></div>
    <div class="kr-expiry"></div>
    <div class="kr-security-code"></div>
    <button class="kr-payment-button"></button>
    <div class="kr-form-error"></div>
  </div>
</div>

@if ($izipay_public_key)
<script
  src="{{ $izipay_js_client_url }}"
  kr-public-key="{{ $izipay_public_key }}"
  nonce="{{ $cspNonce }}"></script>
<link rel="stylesheet" href="https://static.micuentaweb.pe/static/js/krypton-client/V4.0/ext/classic.css">
<script src="https://static.micuentaweb.pe/static/js/krypton-client/V4.0/ext/classic.js" nonce="{{ $cspNonce }}"></script>
@endif

@push('scripts')
<script nonce="{{ $cspNonce }}">
  const CSRF_TOKEN = @json(csrf_token());
  const URLS = {
    formToken: @json(route('izipay.form-token')),
    validar:   @json(route('izipay.validar')),
  };

  let productoActual = null;
  let intentoEnviado = false;
  const modalDatos = new bootstrap.Modal(document.getElementById('modalDatos'));
  const btnContinuar = document.getElementById('btnContinuar');
  const btnContinuarTextoOriginal = btnContinuar.textContent;

  // 1) Click en "Comprar" -> abrir el modal de datos.
  document.querySelectorAll('.btn-comprar').forEach(function (btn) {
    btn.addEventListener('click', function () {
      productoActual = {
        slug:   btn.dataset.slug,
        nombre: btn.dataset.nombre,
        monto:  parseInt(btn.dataset.monto, 10),
      };
      document.getElementById('modalProducto').textContent =
        productoActual.nombre + ' — S/ ' + (productoActual.monto / 100).toFixed(2);
      ocultarErrorModal();
      restaurarBotonContinuar();
      modalDatos.show();
    });
  });

  // 2) Continuar -> pedir el formToken al backend (el monto SIEMPRE lo calcula el servidor) y abrir el PopIn.
  btnContinuar.addEventListener('click', function () {
    const first_name = document.getElementById('f_first_name').value.trim();
    const last_name  = document.getElementById('f_last_name').value.trim();
    const email      = document.getElementById('f_email').value.trim();
    const phone      = document.getElementById('f_phone').value.trim();
    const dni        = document.getElementById('f_dni').value.trim();

    if (!first_name || !last_name || !email || !phone || !dni) {
      return mostrarErrorModal('Completa todos los campos para continuar.');
    }

    ocultarErrorModal();
    btnContinuar.disabled = true;
    btnContinuar.textContent = 'Procesando...';

    postJson(URLS.formToken, {
      producto:      productoActual.slug,
      first_name:    first_name,
      last_name:     last_name,
      email:         email,
      phone_number:  phone,
      identity_code: dni,
    }).then(function (data) {
      if (!data.ok) {
        restaurarBotonContinuar();
        // data.mensaje: error propio del controlador. data.message/errors:
        // formato por defecto de Laravel cuando falla la validacion de $request.
        const primerErrorValidacion = data.errors && Object.values(data.errors)[0];
        const mensaje = data.mensaje
          || (primerErrorValidacion && primerErrorValidacion[0])
          || data.message
          || 'No se pudo iniciar el pago.';
        return mostrarErrorModal(mensaje);
      }
      prepararYAbrirPopin(data.form_token);
    }).catch(function () {
      restaurarBotonContinuar();
      mostrarErrorModal('Error de conexión. Intenta nuevamente.');
    });
  });

  function restaurarBotonContinuar() {
    btnContinuar.disabled = false;
    btnContinuar.textContent = btnContinuarTextoOriginal;
  }

  const elModalDatos = document.getElementById('modalDatos');

  // 3) Espera a que el modal de datos termine de cerrarse (evento
  //    'hidden.bs.modal', no basta con llamar a hide()) antes de abrir el
  //    PopIn de Izipay. Abrirlo mientras Bootstrap todavia esta a mitad de
  //    la transicion de cierre (con su trampa de foco y backdrop activos)
  //    es lo que hacia que el PopIn a veces no apareciera solo.
  function prepararYAbrirPopin(formToken) {
    elModalDatos.addEventListener('hidden.bs.modal', function onHidden() {
      elModalDatos.removeEventListener('hidden.bs.modal', onHidden);
      abrirPopin(formToken);
    });
    modalDatos.hide();
  }

  // 4) Asigna el formToken al PopIn y lo abre.
  function abrirPopin(formToken) {
    if (typeof KR === 'undefined') {
      restaurarBotonContinuar();
      return mostrarResultado('error', 'No se pudo cargar la pasarela de pago. Recarga la página.');
    }

    intentoEnviado = false;

    // Callback (no Promise) por compatibilidad con la version del cliente Krypton
    // servida por micuentaweb.pe.
    KR.setFormToken(formToken, function () {
      restaurarBotonContinuar();
      // KR.openPopin() es el metodo documentado para abrir un "kr-popin"
      // por codigo; simular un click en el boton oculto (como se hacia
      // antes) es fragil y podia no disparar la apertura.
      if (typeof KR.openPopin === 'function') {
        return KR.openPopin();
      }
      const btnPago = document.querySelector('#izipay-popin .kr-payment-button');
      if (btnPago) {
        btnPago.click();
      } else {
        mostrarResultado('error', 'No se pudo abrir el formulario de pago. Recarga la página e intenta de nuevo.');
      }
    });
  }

  // 5) Callback que Izipay invoca tras el intento de pago (KR.onSubmit).
  //    Se retorna false para evitar la redirección/POST por defecto del formulario:
  //    el resultado se envía por fetch() a /izipay/validar, igual que el resto del flujo.
  if (typeof KR !== 'undefined') {
    KR.onSubmit(function (paymentResponse) {
      intentoEnviado = true;

      postJson(URLS.validar, {
        'kr-answer':        paymentResponse.rawClientAnswer,
        'kr-hash':           paymentResponse.hash,
        'kr-hash-algorithm': paymentResponse.hashAlgorithm,
        'kr-hash-key':       paymentResponse.hashKey,
      }).then(resolverResultado).catch(errorConexion);

      return false;
    });

    KR.onError(function () {
      mostrarResultado('error', 'No se pudo procesar el pago. Intenta con otra tarjeta.');
    });

    // Se dispara al cerrar el PopIn, tanto si el usuario pagó como si lo
    // cerró sin intentarlo. Solo avisamos del cierre manual: si ya hubo un
    // intento, KR.onSubmit/resolverResultado ya se encargó del mensaje.
    if (typeof KR.onPopinClosed === 'function') {
      KR.onPopinClosed(function () {
        if (!intentoEnviado) {
          mostrarResultado('info', 'Cerraste el formulario de pago sin completarlo. Puedes intentarlo de nuevo cuando quieras.');
        }
      });
    }
  }

  function resolverResultado(data) {
    // El popin no se cierra solo porque KR.onSubmit devuelve false (así lo
    // exige validar el pago por fetch() contra el backend en vez de dejar
    // que Krypton redirija). Una vez que ya tenemos la respuesta final del
    // pago, cerramos el popin nosotros para que el mensaje de resultado sea
    // visible.
    cerrarPopin();

    if (data.ok) {
      mostrarResultado('ok', data.mensaje);
    } else if (data.pendiente) {
      mostrarResultado('pendiente', data.mensaje);
    } else {
      mostrarResultado('error', data.mensaje || 'No se pudo procesar el pago.');
    }
  }

  function errorConexion() {
    cerrarPopin();
    mostrarResultado('error', 'Error de conexión al procesar el pago.');
  }

  function cerrarPopin() {
    if (typeof KR !== 'undefined' && typeof KR.closePopin === 'function') {
      KR.closePopin();
    }
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
    const box = document.getElementById('pago-resultado');
    box.className = 'mb-4 ' + tipo;
    box.innerHTML = '';

    const icono = document.createElement('span');
    icono.setAttribute('aria-hidden', 'true');

    if (tipo === 'pendiente') {
      // Sigue confirmándose (esperando el IPN) -> spinner, no un icono fijo.
      icono.className = 'pago-resultado-spinner';
    } else {
      icono.className = 'pago-resultado-icono ' + tipo;
      icono.textContent = { ok: '✓', error: '✕', info: 'i' }[tipo] || '';
    }

    const texto = document.createElement('span');
    texto.textContent = mensaje;

    box.appendChild(icono);
    box.appendChild(texto);
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
