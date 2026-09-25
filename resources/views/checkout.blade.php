@extends('layouts.app')

@section('content')

<style>
  .checkout-col-form { max-width: 640px; }
  .checkout-seccion { margin-bottom: 28px; }
  .checkout-seccion h2 { font-size: 16px; margin-bottom: 14px; color: var(--enlix-ink); }

  .checkout-resumen {
    background: #fff; border: 1px solid var(--enlix-border); border-radius: 8px;
    padding: 24px; position: sticky; top: 100px;
  }
  .checkout-resumen h2 { font-size: 18px; margin: 0 0 16px; }
  .checkout-resumen-item { display: flex; justify-content: space-between; font-size: 14px; padding: 6px 0; color: var(--enlix-ink); }
  .checkout-resumen-item .cantidad { color: var(--enlix-muted); }
  .checkout-resumen-linea { display: flex; justify-content: space-between; font-size: 15px; color: var(--enlix-ink); margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--enlix-border); }
  .checkout-resumen-linea.total { font-size: 20px; font-weight: 700; }

  #pago-resultado {
    max-width: 640px; margin: 0 0 20px; display: none; border-radius: 6px; padding: 16px 20px;
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
  .pago-resultado-spinner {
    width: 20px; height: 20px; border-radius: 50%; flex: none;
    border: 3px solid rgba(30, 78, 140, .2); border-top-color: #1e4e8c;
    animation: pagoResultadoSpin .8s linear infinite;
  }
  @keyframes pagoResultadoIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes pagoResultadoIconoIn { from { transform: scale(0); } to { transform: scale(1); } }
  @keyframes pagoResultadoSpin { to { transform: rotate(360deg); } }

  #izipay-popin-wrapper { display: flex; justify-content: center; margin-top: 16px; }
</style>

<section class="section-pad">
  <div class="container">

    <div class="mb-5">
      <p class="svc-block-num">Tienda</p>
      <h1>Checkout</h1>
    </div>

    <div id="pago-resultado"></div>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="checkout-col-form">

          <div class="checkout-seccion">
            <h2>1. Datos de contacto</h2>
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
                <input type="text" class="form-control" id="f_telefono" placeholder="+51 999 999 999" autocomplete="tel">
              </div>
            </div>
          </div>

          <div class="checkout-seccion">
            <h2>2. Comprobante</h2>
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label">Tipo de comprobante</label>
                <select class="form-select" id="f_tipo_comprobante">
                  <option value="boleta">Boleta</option>
                  <option value="factura">Factura</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label">Tipo de documento</label>
                <select class="form-select" id="f_tipo_documento">
                  <option value="DNI">DNI</option>
                  <option value="RUC">RUC</option>
                  <option value="CE">Carné de extranjería</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label" id="label_numero_documento">Número de DNI</label>
                <input type="text" class="form-control" id="f_numero_documento" maxlength="15">
              </div>
              <div class="col-12" id="grupo_razon_social" style="display: none;">
                <label class="form-label">Razón social</label>
                <input type="text" class="form-control" id="f_razon_social">
              </div>
            </div>
          </div>

          <div class="checkout-seccion">
            <h2>3. Entrega</h2>
            <div class="row g-3">
              <div class="col-12">
                <select class="form-select" id="f_metodo_entrega">
                  <option value="recojo">Recojo en tienda</option>
                  <option value="envio">Envío a domicilio</option>
                </select>
              </div>
              <div id="grupo_envio" style="display: none;" class="row g-3">
                <div class="col-12">
                  <label class="form-label">Dirección</label>
                  <input type="text" class="form-control" id="f_direccion">
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Distrito</label>
                  <input type="text" class="form-control" id="f_distrito">
                </div>
                <div class="col-sm-6">
                  <label class="form-label">Ciudad</label>
                  <input type="text" class="form-control" id="f_ciudad">
                </div>
                <div class="col-12">
                  <label class="form-label">Referencia (opcional)</label>
                  <input type="text" class="form-control" id="f_referencia">
                </div>
              </div>
            </div>
          </div>

          <div id="checkout-error" class="text-danger mb-3" style="font-size: 13px; display: none;"></div>

        </div>
      </div>

      <div class="col-lg-5">
        <div class="checkout-resumen">
          <h2>Resumen del pedido</h2>

          @foreach ($resumen['items'] as $item)
            <div class="checkout-resumen-item">
              <span>{{ $item['nombre'] }} <span class="cantidad">x{{ $item['cantidad'] }}</span></span>
              <span>S/ {{ number_format($item['subtotal_centimos'] / 100, 2) }}</span>
            </div>
          @endforeach

          <div class="checkout-resumen-linea total">
            <span>Total</span>
            <span>S/ {{ number_format($resumen['subtotal_centimos'] / 100, 2) }}</span>
          </div>

          <button type="button" class="btn btn-primary-enlix w-100 mt-3" id="btnContinuar">Continuar al pago</button>

          {{-- Vacío a propósito: el kr-embedded/kr-popin de Krypton se
               inyecta recién por JS cuando hay formToken (ver abrirPopin()).
               Si el <div class="kr-embedded" kr-popin> vive en el HTML desde
               el principio, Krypton lo renderiza inline (botón "PAY" en
               inglés) antes de que exista un pago que iniciar, duplicando el
               botón "Continuar al pago" y confundiendo al usuario. --}}
          <div id="izipay-popin-wrapper"></div>

          <p class="text-center mt-3 mb-0" style="font-size: 12.5px; color: var(--enlix-muted);">
            🔒 Pago seguro procesado por Izipay. No almacenamos los datos de tu tarjeta.
          </p>
        </div>
      </div>
    </div>

  </div>
</section>

@if ($izipay_public_key)
<script
  src="{{ $izipay_js_client_url }}"
  kr-public-key="{{ $izipay_public_key }}"
  kr-language="es-Es"
  nonce="{{ $cspNonce }}"></script>
<link rel="stylesheet" href="https://static.micuentaweb.pe/static/js/krypton-client/V4.0/ext/classic.css">
<script src="https://static.micuentaweb.pe/static/js/krypton-client/V4.0/ext/classic.js" nonce="{{ $cspNonce }}"></script>
@endif

@push('scripts')
<script nonce="{{ $cspNonce }}">
  const CSRF_TOKEN = @json(csrf_token());
  const URL_CREAR_PEDIDO = @json(route('checkout.crear'));
  const URL_VALIDAR = @json(route('checkout.validar'));

  let intentoEnviado = false;
  const btnContinuar = document.getElementById('btnContinuar');
  const btnContinuarTextoOriginal = btnContinuar.textContent;

  // Mostrar/ocultar campos según tipo de comprobante y método de entrega.
  const selTipoComprobante = document.getElementById('f_tipo_comprobante');
  const selTipoDocumento = document.getElementById('f_tipo_documento');
  const grupoRazonSocial = document.getElementById('grupo_razon_social');
  const labelNumeroDocumento = document.getElementById('label_numero_documento');
  const selMetodoEntrega = document.getElementById('f_metodo_entrega');
  const grupoEnvio = document.getElementById('grupo_envio');

  function sincronizarComprobante() {
    const esFactura = selTipoComprobante.value === 'factura';
    grupoRazonSocial.style.display = esFactura ? '' : 'none';
    selTipoDocumento.value = esFactura ? 'RUC' : 'DNI';
    actualizarLabelDocumento();
  }

  function actualizarLabelDocumento() {
    const etiquetas = { DNI: 'Número de DNI', RUC: 'Número de RUC', CE: 'Número de carné de extranjería' };
    labelNumeroDocumento.textContent = etiquetas[selTipoDocumento.value] || 'Número de documento';
  }

  function sincronizarEntrega() {
    grupoEnvio.style.display = selMetodoEntrega.value === 'envio' ? 'flex' : 'none';
  }

  selTipoComprobante.addEventListener('change', sincronizarComprobante);
  selTipoDocumento.addEventListener('change', actualizarLabelDocumento);
  selMetodoEntrega.addEventListener('change', sincronizarEntrega);
  sincronizarComprobante();
  sincronizarEntrega();

  btnContinuar.addEventListener('click', function () {
    const datos = {
      first_name: document.getElementById('f_first_name').value.trim(),
      last_name: document.getElementById('f_last_name').value.trim(),
      email: document.getElementById('f_email').value.trim(),
      telefono: document.getElementById('f_telefono').value.trim(),
      tipo_documento: selTipoDocumento.value,
      numero_documento: document.getElementById('f_numero_documento').value.trim(),
      tipo_comprobante: selTipoComprobante.value,
      razon_social: document.getElementById('f_razon_social').value.trim(),
      metodo_entrega: selMetodoEntrega.value,
      direccion: document.getElementById('f_direccion').value.trim(),
      distrito: document.getElementById('f_distrito').value.trim(),
      ciudad: document.getElementById('f_ciudad').value.trim(),
      referencia: document.getElementById('f_referencia').value.trim(),
    };

    if (!datos.first_name || !datos.last_name || !datos.email || !datos.telefono || !datos.numero_documento) {
      return mostrarErrorCheckout('Completa todos los campos obligatorios para continuar.');
    }
    if (datos.tipo_comprobante === 'factura' && !datos.razon_social) {
      return mostrarErrorCheckout('La razón social es obligatoria para factura.');
    }
    if (datos.metodo_entrega === 'envio' && (!datos.direccion || !datos.distrito)) {
      return mostrarErrorCheckout('Completa la dirección y el distrito para el envío.');
    }

    ocultarErrorCheckout();
    btnContinuar.disabled = true;
    btnContinuar.textContent = 'Procesando...';

    fetchJsonEnlix('POST', URL_CREAR_PEDIDO, datos, CSRF_TOKEN).then(function (data) {
      if (!data.ok) {
        restaurarBotonContinuar();
        const primerErrorValidacion = data.errors && Object.values(data.errors)[0];
        const mensaje = data.mensaje
          || (primerErrorValidacion && primerErrorValidacion[0])
          || data.message
          || 'No se pudo iniciar el pago.';
        return mostrarErrorCheckout(mensaje);
      }
      abrirPopin(data.form_token);
    }).catch(function () {
      restaurarBotonContinuar();
      mostrarErrorCheckout('Error de conexión. Intenta nuevamente.');
    });
  });

  function restaurarBotonContinuar() {
    btnContinuar.disabled = false;
    btnContinuar.textContent = btnContinuarTextoOriginal;
  }

  function abrirPopin(formToken) {
    if (typeof KR === 'undefined') {
      restaurarBotonContinuar();
      return mostrarResultado('error', 'No se pudo cargar la pasarela de pago. Recarga la página.');
    }

    intentoEnviado = false;

    // Recién aquí se crean los campos reales que Krypton necesita en el DOM
    // (ver comentario en el HTML): antes de este punto no había ningún pago
    // que mostrar, así que no había razón para que el botón nativo existiera.
    const wrapper = document.getElementById('izipay-popin-wrapper');
    wrapper.innerHTML = '';
    const popin = document.createElement('div');
    popin.className = 'kr-embedded';
    popin.id = 'izipay-popin';
    popin.setAttribute('kr-popin', '');
    popin.innerHTML =
      '<div class="kr-pan"></div>' +
      '<div class="kr-expiry"></div>' +
      '<div class="kr-security-code"></div>' +
      '<button class="kr-payment-button"></button>' +
      '<div class="kr-form-error"></div>';
    wrapper.appendChild(popin);

    KR.setFormToken(formToken, function () {
      restaurarBotonContinuar();
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

  if (typeof KR !== 'undefined') {
    KR.onSubmit(function (paymentResponse) {
      intentoEnviado = true;

      fetchJsonEnlix('POST', URL_VALIDAR, {
        'kr-answer': paymentResponse.rawClientAnswer,
        'kr-hash': paymentResponse.hash,
        'kr-hash-algorithm': paymentResponse.hashAlgorithm,
        'kr-hash-key': paymentResponse.hashKey,
      }, CSRF_TOKEN).then(resolverResultado).catch(errorConexion);

      return false;
    });

    KR.onError(function () {
      mostrarResultado('error', 'No se pudo procesar el pago. Intenta con otra tarjeta.');
    });

    if (typeof KR.onPopinClosed === 'function') {
      KR.onPopinClosed(function () {
        if (!intentoEnviado) {
          mostrarResultado('info', 'Cerraste el formulario de pago sin completarlo. Puedes intentarlo de nuevo cuando quieras.');
        }
      });
    }
  }

  function resolverResultado(data) {
    cerrarPopin();

    if (data.ok) {
      mostrarResultado('ok', data.mensaje);
      actualizarBadgeCarrito(0);
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

  function mostrarResultado(tipo, mensaje) {
    const box = document.getElementById('pago-resultado');
    box.className = tipo;
    box.innerHTML = '';

    const icono = document.createElement('span');
    icono.setAttribute('aria-hidden', 'true');

    if (tipo === 'pendiente') {
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

  function mostrarErrorCheckout(mensaje) {
    const e = document.getElementById('checkout-error');
    e.textContent = mensaje;
    e.style.display = 'block';
    e.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function ocultarErrorCheckout() {
    document.getElementById('checkout-error').style.display = 'none';
  }
</script>
@endpush

@endsection
