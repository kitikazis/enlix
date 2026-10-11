@extends('layouts.app')

@section('content')

<style>
  .reclamo-form-col { max-width: 720px; }
  .reclamo-seccion { margin-bottom: 28px; }
  .reclamo-seccion h2 { font-size: 16px; margin-bottom: 4px; color: var(--enlix-ink); }
  .reclamo-seccion .ayuda { font-size: 13.5px; color: var(--enlix-muted); margin-bottom: 14px; }

  .reclamo-plazo {
    background: var(--enlix-blue-50); border: 1px solid var(--enlix-border);
    border-radius: 8px; padding: 14px 18px; font-size: 14px; color: var(--enlix-ink);
    margin-bottom: 28px; display: flex; gap: 10px; align-items: flex-start;
  }

  .reclamo-tipo-opciones { display: flex; flex-direction: column; gap: 10px; }
  .reclamo-tipo-opcion {
    display: flex; gap: 10px; align-items: flex-start; border: 1px solid var(--enlix-border);
    border-radius: 8px; padding: 12px 14px; cursor: pointer;
  }
  .reclamo-tipo-opcion input { margin-top: 3px; }
  .reclamo-tipo-opcion .titulo { font-weight: 600; font-size: 14.5px; color: var(--enlix-ink); }
  .reclamo-tipo-opcion .desc { font-size: 13px; color: var(--enlix-muted); }

  /* Areas tactiles >= 44px y texto base 16px (sin zoom automatico en iOS). */
  .reclamo-form-col .form-control,
  .reclamo-form-col .form-select,
  .reclamo-form-col textarea {
    font-size: 16px;
    min-height: 44px;
  }
  .reclamo-form-col .form-check-input { width: 20px; height: 20px; }
  .reclamo-form-col button[type="submit"] { min-height: 48px; }
</style>

<section class="section-pad">
  <div class="container">

    <div class="mb-4">
      <p class="svc-block-num">Legal</p>
      <h1>Libro de Reclamaciones</h1>
      <p class="text-muted-enlix" style="max-width: 640px;">
        Conforme al Código de Protección y Defensa del Consumidor, puedes registrar aquí tu reclamo o queja
        sobre un producto o servicio adquirido en Enlix.
      </p>
    </div>

    <div class="reclamo-form-col">

      <div class="reclamo-plazo">
        <span aria-hidden="true">⏱️</span>
        <span>Tenemos un plazo máximo de <strong>15 días hábiles</strong> para dar respuesta a tu reclamo o queja.</span>
      </div>

      @if ($errors->any())
        <div class="alert alert-danger" role="alert">
          <strong>Revisa estos campos:</strong>
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('libro-reclamaciones.store') }}" id="formReclamo">
        @csrf

        <div class="reclamo-seccion">
          <h2>1. Datos del consumidor</h2>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Nombre completo</label>
              <input type="text" name="nombre_consumidor" class="form-control" value="{{ old('nombre_consumidor') }}" required>
            </div>
            <div class="col-sm-5">
              <label class="form-label">Tipo de documento</label>
              <select name="tipo_documento_consumidor" class="form-select" id="f_tipo_documento">
                <option value="DNI" @selected(old('tipo_documento_consumidor', 'DNI') === 'DNI')>DNI</option>
                <option value="RUC" @selected(old('tipo_documento_consumidor') === 'RUC')>RUC</option>
                <option value="CE" @selected(old('tipo_documento_consumidor') === 'CE')>Carné de extranjería</option>
              </select>
            </div>
            <div class="col-sm-7">
              <label class="form-label">N.° de documento</label>
              <input type="text" name="numero_documento_consumidor" class="form-control" inputmode="numeric" maxlength="15" value="{{ old('numero_documento_consumidor') }}" required>
            </div>
            <div class="col-12">
              <label class="form-label">Domicilio</label>
              <input type="text" name="domicilio_consumidor" class="form-control" value="{{ old('domicilio_consumidor') }}" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label">Teléfono</label>
              <input type="text" name="telefono_consumidor" class="form-control" inputmode="tel" placeholder="+51 999 999 999" value="{{ old('telefono_consumidor') }}" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label">Correo electrónico</label>
              <input type="email" name="correo_consumidor" class="form-control" value="{{ old('correo_consumidor') }}" required>
            </div>
          </div>
        </div>

        <div class="reclamo-seccion">
          <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="f_es_menor_edad" name="es_menor_edad" value="1" @checked(old('es_menor_edad'))>
            <label class="form-check-label" for="f_es_menor_edad">El consumidor es menor de edad</label>
          </div>

          <div id="grupo_padre_tutor" class="row g-3" style="display: {{ old('es_menor_edad') ? '' : 'none' }};">
            <div class="col-sm-6">
              <label class="form-label">Nombre del padre o tutor</label>
              <input type="text" name="nombre_padre_tutor" class="form-control" value="{{ old('nombre_padre_tutor') }}">
            </div>
            <div class="col-sm-6">
              <label class="form-label">Documento del padre o tutor</label>
              <input type="text" name="documento_padre_tutor" class="form-control" inputmode="numeric" value="{{ old('documento_padre_tutor') }}">
            </div>
          </div>
        </div>

        <div class="reclamo-seccion">
          <h2>2. Identificación del producto o servicio</h2>
          <div class="row g-3">
            <div class="col-sm-5">
              <label class="form-label">Tipo</label>
              <select name="tipo_bien" class="form-select">
                <option value="producto" @selected(old('tipo_bien', 'producto') === 'producto')>Producto</option>
                <option value="servicio" @selected(old('tipo_bien') === 'servicio')>Servicio</option>
              </select>
            </div>
            <div class="col-sm-7">
              <label class="form-label">Monto reclamado (opcional)</label>
              <input type="number" name="monto" class="form-control" inputmode="decimal" step="0.01" min="0" value="{{ old('monto') }}">
            </div>
            <div class="col-12">
              <label class="form-label">Descripción del producto o servicio</label>
              <input type="text" name="descripcion_bien" class="form-control" value="{{ old('descripcion_bien') }}" required>
            </div>
          </div>
        </div>

        <div class="reclamo-seccion">
          <h2>3. Tipo</h2>
          <div class="reclamo-tipo-opciones">
            <label class="reclamo-tipo-opcion">
              <input type="radio" name="tipo" value="reclamo" @checked(old('tipo', 'reclamo') === 'reclamo')>
              <span>
                <span class="titulo d-block">Reclamo</span>
                <span class="desc">Disconformidad relacionada a los productos o servicios.</span>
              </span>
            </label>
            <label class="reclamo-tipo-opcion">
              <input type="radio" name="tipo" value="queja" @checked(old('tipo') === 'queja')>
              <span>
                <span class="titulo d-block">Queja</span>
                <span class="desc">Malestar o disconformidad no relacionada a los productos o servicios; o mal trato en la atención.</span>
              </span>
            </label>
          </div>
        </div>

        <div class="reclamo-seccion">
          <h2>4. Detalle</h2>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Detalle del reclamo o queja</label>
              <textarea name="detalle" class="form-control" rows="4" required>{{ old('detalle') }}</textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Pedido del consumidor</label>
              <textarea name="pedido_consumidor" class="form-control" rows="3" required>{{ old('pedido_consumidor') }}</textarea>
            </div>
          </div>
        </div>

        <div class="form-check mb-4">
          <input type="checkbox" class="form-check-input" id="f_acepta_privacidad" name="acepta_privacidad" value="1">
          <label class="form-check-label" for="f_acepta_privacidad">
            He leído y acepto la <a href="{{ route('politica-privacidad') }}" target="_blank" rel="noopener">política de privacidad</a>
          </label>
        </div>

        <button type="submit" class="btn btn-primary-enlix w-100">Enviar reclamo</button>
      </form>
    </div>

  </div>
</section>

@push('scripts')
<script nonce="{{ $cspNonce }}">
  var chkMenor = document.getElementById('f_es_menor_edad');
  var grupoPadreTutor = document.getElementById('grupo_padre_tutor');
  chkMenor.addEventListener('change', function () {
    grupoPadreTutor.style.display = chkMenor.checked ? '' : 'none';
  });
</script>
@endpush

@endsection
