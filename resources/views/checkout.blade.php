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

  /* Mientras no hay formToken no se muestra nada del formulario de Izipay. */
  #checkout-pago { display: none; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--enlix-border); }
  #checkout-pago.is-activo { display: block; }
  #checkout-pago h2 { font-size: 16px; margin: 0 0 4px; }
  .checkout-pago-nota { margin: 0 0 16px; font-size: 13px; color: var(--enlix-muted); }
  /* Tema de Izipay a los colores de Enlix. Con #id porque neon-reset carga
     después de este bloque; los campos de tarjeta son iframes de Izipay. */
  #izipay-formulario .kr-smart-form,
  #izipay-formulario .kr-smart-form-wrapper,
  #izipay-formulario .kr-embedded { width: 100%; max-width: none; font-family: var(--font-body); }
  #izipay-formulario .kr-payment-button {
    min-height: 48px;
    background: var(--enlix-blue-800) !important; /* Izipay lo fija con su color */
    border-color: var(--enlix-blue-800) !important;
    border-radius: var(--radius-sm);
    color: #fff !important;
    font-family: var(--font-body);
    font-size: 16px;
    font-weight: 600;
  }
  #izipay-formulario .kr-payment-button:hover { background: var(--enlix-blue-900) !important; border-color: var(--enlix-blue-900) !important; }
  .checkout-resumen.is-pagando { position: static; }
  #checkout-datos:disabled { opacity: .65; }
  .checkout-error { margin: 12px 0 0; font-size: 13px; color: #9b1c1c; }
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
        {{-- Se deshabilita al pasar al pago: el pedido ya se creó con estos datos. --}}
        <fieldset class="checkout-col-form" id="checkout-datos">

          <div class="checkout-seccion">
            <h2>1. Datos de contacto</h2>
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label" for="f_first_name">Nombre</label>
                <input type="text" class="form-control" id="f_first_name" autocomplete="given-name">
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="f_last_name">Apellido</label>
                <input type="text" class="form-control" id="f_last_name" autocomplete="family-name">
              </div>
              <div class="col-12">
                <label class="form-label" for="f_email">Correo electrónico</label>
                <input type="email" class="form-control" id="f_email" autocomplete="email" inputmode="email">
              </div>
              <div class="col-12">
                <label class="form-label" for="f_telefono">Teléfono</label>
                <input type="tel" class="form-control" id="f_telefono" placeholder="+51 999 999 999" autocomplete="tel" inputmode="tel">
              </div>
            </div>
          </div>

          <div class="checkout-seccion">
            <h2>2. Documento</h2>
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label" for="f_tipo_documento">Tipo de documento</label>
                <select class="form-select" id="f_tipo_documento">
                  <option value="DNI">DNI</option>
                  <option value="RUC">RUC</option>
                  <option value="CE">Carné de extranjería</option>
                </select>
              </div>
              <div class="col-sm-6">
                <label class="form-label" id="label_numero_documento" for="f_numero_documento">Número de DNI</label>
                <input type="text" class="form-control" id="f_numero_documento" maxlength="8" inputmode="numeric" autocomplete="off">
              </div>
            </div>
          </div>

          <div class="checkout-seccion">
            <h2>3. Entrega</h2>
            <div class="row g-3">
              <div class="col-12">
                <select class="form-select" id="f_metodo_entrega" aria-label="Método de entrega">
                  <option value="recojo">Recojo en tienda</option>
                  <option value="envio">Envío a domicilio</option>
                </select>
              </div>
              <div id="grupo_envio" style="display: none;" class="row g-3">
                <div class="col-12">
                  <label class="form-label" for="f_direccion">Dirección</label>
                  <input type="text" class="form-control" id="f_direccion" autocomplete="street-address">
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="f_distrito">Distrito</label>
                  <input type="text" class="form-control" id="f_distrito">
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="f_ciudad">Ciudad</label>
                  <input type="text" class="form-control" id="f_ciudad">
                </div>
                <div class="col-12">
                  <label class="form-label" for="f_referencia">Referencia (opcional)</label>
                  <input type="text" class="form-control" id="f_referencia">
                </div>
              </div>
            </div>
          </div>

        </fieldset>
      </div>

      <div class="col-lg-5">
        <div class="checkout-resumen" id="checkout-resumen">
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

          <div class="form-check mt-3">
            <input type="checkbox" class="form-check-input" id="f_terminos">
            <label class="form-check-label" for="f_terminos" style="font-size: 13px;">
              Acepto los <a href="{{ route('terminos') }}" target="_blank" rel="noopener">términos y condiciones</a>
            </label>
          </div>

          <div class="form-check mt-2">
            <input type="checkbox" class="form-check-input" id="f_privacidad">
            <label class="form-check-label" for="f_privacidad" style="font-size: 13px;">
              He leído y acepto la <a href="{{ route('politica-privacidad') }}" target="_blank" rel="noopener">política de privacidad</a>
            </label>
          </div>

          <p id="checkout-error" class="checkout-error" role="alert" hidden></p>

          <button type="button" class="btn btn-primary-enlix w-100 mt-3" id="btnContinuar">Continuar al pago</button>

          <div id="checkout-pago" aria-labelledby="checkout-pago-titulo">
            <h2 id="checkout-pago-titulo" tabindex="-1">Elige cómo pagar</h2>
            <p class="checkout-pago-nota">Tus datos quedaron registrados en el pedido.</p>
            {{-- Vacío a propósito: el kr-smart-form se inyecta recién por JS
                 cuando hay formToken (ver mostrarFormularioPago()). Si vive en
                 el HTML desde el principio, Krypton lo renderiza antes de que
                 exista un pago que iniciar. --}}
            <div id="izipay-formulario"></div>
          </div>

          <p class="text-center mt-3 mb-0" style="font-size: 12.5px; color: var(--enlix-muted);">
            🔒 Pago seguro procesado por Izipay. No almacenamos tus datos de pago.
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
  kr-language="es-PE"
  nonce="{{ $cspNonce }}"></script>
<link rel="stylesheet" href="https://static.micuentaweb.pe/static/js/krypton-client/V4.0/ext/neon-reset.min.css">
<script src="https://static.micuentaweb.pe/static/js/krypton-client/V4.0/ext/neon.js" nonce="{{ $cspNonce }}"></script>
@endif

@push('scripts')
<script nonce="{{ $cspNonce }}">
  const CSRF_TOKEN = @json(csrf_token());
  const URL_CREAR_PEDIDO = @json(route('checkout.crear'));
  const URL_VALIDAR = @json(route('checkout.validar'));
  const URL_RESUMEN_CARRITO = @json(route('carrito.resumen'));
  // Ids de los items que el cliente dejó marcados en /carrito (ver checkbox
  // de selección); null = todo el carrito. Viajan tal cual al POST de abajo.
  const ITEMS_SELECCIONADOS = @json(isset($items_seleccionados) ? implode(',', $items_seleccionados) : null);

  const btnContinuar = document.getElementById('btnContinuar');
  const btnContinuarTextoOriginal = btnContinuar.textContent;

  // Mostrar/ocultar campos según método de entrega. El comprobante ya no se
  // pide aquí: siempre se emite boleta (ver CheckoutCrearRequest) y quien la
  // emite es el equipo de Enlix por fuera del sistema, no la web.
  const selTipoDocumento = document.getElementById('f_tipo_documento');
  const labelNumeroDocumento = document.getElementById('label_numero_documento');
  const selMetodoEntrega = document.getElementById('f_metodo_entrega');
  const grupoEnvio = document.getElementById('grupo_envio');

  // Mismos largos que CheckoutCrearRequest. El CE no tiene formato fijo.
  const DOCUMENTOS = {
    DNI: { etiqueta: 'Número de DNI', maximo: 8, soloNumeros: true },
    RUC: { etiqueta: 'Número de RUC', maximo: 11, soloNumeros: true },
    CE: { etiqueta: 'Número de carné de extranjería', maximo: 15, soloNumeros: false },
  };
  const inputDocumento = document.getElementById('f_numero_documento');

  // maxlength no alcanza: no filtra letras ni lo que se pegue en el campo.
  function normalizarNumeroDocumento() {
    const documento = DOCUMENTOS[selTipoDocumento.value];
    const valor = documento.soloNumeros ? inputDocumento.value.replace(/\D/g, '') : inputDocumento.value;
    inputDocumento.value = valor.slice(0, documento.maximo);
  }

  function actualizarLabelDocumento() {
    const documento = DOCUMENTOS[selTipoDocumento.value];
    labelNumeroDocumento.textContent = documento.etiqueta;
    inputDocumento.maxLength = documento.maximo;
    inputDocumento.inputMode = documento.soloNumeros ? 'numeric' : 'text';
    normalizarNumeroDocumento();
  }

  function sincronizarEntrega() {
    grupoEnvio.style.display = selMetodoEntrega.value === 'envio' ? 'flex' : 'none';
  }

  selTipoDocumento.addEventListener('change', function () {
    // Vaciar y no recortar: un RUC cortado a 8 dígitos parecería un DNI válido.
    inputDocumento.value = '';
    actualizarLabelDocumento();
  });
  inputDocumento.addEventListener('input', normalizarNumeroDocumento);
  selMetodoEntrega.addEventListener('change', sincronizarEntrega);
  actualizarLabelDocumento();
  sincronizarEntrega();

  btnContinuar.addEventListener('click', function () {
    const datos = {
      first_name: document.getElementById('f_first_name').value.trim(),
      last_name: document.getElementById('f_last_name').value.trim(),
      email: document.getElementById('f_email').value.trim(),
      telefono: document.getElementById('f_telefono').value.trim(),
      tipo_documento: selTipoDocumento.value,
      numero_documento: document.getElementById('f_numero_documento').value.trim(),
      tipo_comprobante: 'boleta',
      razon_social: '',
      metodo_entrega: selMetodoEntrega.value,
      direccion: document.getElementById('f_direccion').value.trim(),
      distrito: document.getElementById('f_distrito').value.trim(),
      ciudad: document.getElementById('f_ciudad').value.trim(),
      referencia: document.getElementById('f_referencia').value.trim(),
      terminos: document.getElementById('f_terminos').checked,
      acepta_privacidad: document.getElementById('f_privacidad').checked,
      items: ITEMS_SELECCIONADOS,
    };

    if (!datos.first_name || !datos.last_name || !datos.email || !datos.telefono || !datos.numero_documento) {
      return mostrarErrorCheckout('Completa todos los campos obligatorios para continuar.');
    }
    if (datos.metodo_entrega === 'envio' && (!datos.direccion || !datos.distrito)) {
      return mostrarErrorCheckout('Completa la dirección y el distrito para el envío.');
    }
    if (!datos.terminos) {
      return mostrarErrorCheckout('Debes aceptar los términos y condiciones para continuar.');
    }
    if (!datos.acepta_privacidad) {
      return mostrarErrorCheckout('Debes aceptar la política de privacidad para continuar.');
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
      mostrarFormularioPago(data.form_token);
    }).catch(function () {
      restaurarBotonContinuar();
      mostrarErrorCheckout('Error de conexión. Intenta nuevamente.');
    });
  });

  function restaurarBotonContinuar() {
    btnContinuar.disabled = false;
    btnContinuar.textContent = btnContinuarTextoOriginal;
  }

  // El pedido ya existe y el carrito ya se vació (ver CheckoutController::crear):
  // "Continuar" no debe volver a usarse, se reintenta en el propio formulario.
  function mostrarFormularioPago(formToken) {
    if (typeof KR === 'undefined') {
      restaurarBotonContinuar();
      return mostrarResultado('error', 'No se pudo cargar la pasarela de pago. Recarga la página.');
    }

    // kr-smart-form muestra todos los medios activos en la cuenta de Izipay;
    // kr-card-form-expanded deja la tarjeta abierta sin un clic extra.
    const formulario = document.createElement('div');
    formulario.className = 'kr-smart-form';
    formulario.setAttribute('kr-card-form-expanded', '');
    document.getElementById('izipay-formulario').replaceChildren(formulario);

    // setFormToken es asíncrono y solo recibe el token: no acepta callback.
    KR.setFormToken(formToken).then(function () {
      btnContinuar.hidden = true;
      document.getElementById('checkout-datos').disabled = true;
      document.getElementById('f_terminos').disabled = true;
      document.getElementById('f_privacidad').disabled = true;
      document.getElementById('checkout-resumen').classList.add('is-pagando');

      const panel = document.getElementById('checkout-pago');
      panel.classList.add('is-activo');
      panel.scrollIntoView({ behavior: 'smooth', block: 'start' });

      // El servidor ya sacó del carrito lo que entró al pedido.
      fetchJsonEnlix('GET', URL_RESUMEN_CARRITO, null, CSRF_TOKEN)
        .then(function (data) { actualizarBadgeCarrito(data.cantidad_total); })
        .catch(function () {});
    }).catch(function () {
      btnContinuar.hidden = true;
      mostrarResultado('error', 'No se pudo abrir el formulario de pago. Escríbenos por WhatsApp para completar tu compra.');
    });
  }

  if (typeof KR !== 'undefined') {
    KR.onSubmit(function (paymentResponse) {
      fetchJsonEnlix('POST', URL_VALIDAR, {
        'kr-answer': paymentResponse.rawClientAnswer,
        'kr-hash': paymentResponse.hash,
        'kr-hash-algorithm': paymentResponse.hashAlgorithm,
        'kr-hash-key': paymentResponse.hashKey,
      }, CSRF_TOKEN).then(resolverResultado).catch(errorConexion);

      return false;
    });

    // Tarjeta rechazada o campos inválidos: el propio formulario lo muestra
    // y deja reintentar, aquí no hace falta un KR.onError.
  }

  function resolverResultado(data) {
    quitarFormularioPago();

    if (data.ok) {
      mostrarResultado('ok', data.mensaje);
    } else if (data.pendiente) {
      mostrarResultado('pendiente', data.mensaje);
    } else {
      mostrarResultado('error', data.mensaje || 'No se pudo procesar el pago.');
    }
  }

  function errorConexion() {
    quitarFormularioPago();
    mostrarResultado('error', 'Error de conexión al confirmar el pago. Revisa tu correo antes de reintentar: el cobro pudo haberse realizado.');
  }

  // El pago ya se intentó: no debe quedar un formulario que invite a pagar otra vez.
  function quitarFormularioPago() {
    document.getElementById('checkout-pago').classList.remove('is-activo');
    KR.removeForms();
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
    e.hidden = false;
    e.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function ocultarErrorCheckout() {
    document.getElementById('checkout-error').hidden = true;
  }
</script>
@endpush

@endsection
