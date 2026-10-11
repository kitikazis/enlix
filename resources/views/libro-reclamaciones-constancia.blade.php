@extends('layouts.app')

@section('content')

<style>
  .constancia-col { max-width: 720px; }
  .constancia-ok {
    display: flex; gap: 12px; align-items: center;
    background: #e8f7ee; color: #176b3a; border: 1px solid #aadcbf;
    border-radius: 8px; padding: 16px 20px; margin-bottom: 24px;
  }
  .constancia-card {
    background: #fff; border: 1px solid var(--enlix-border); border-radius: 8px; padding: 28px;
  }
  .constancia-numero { font-size: 13px; color: var(--enlix-muted); margin: 0 0 4px; }
  .constancia-numero strong { color: var(--enlix-ink); font-size: 16px; }
  .constancia-fila { display: flex; justify-content: space-between; gap: 16px; padding: 10px 0; border-bottom: 1px solid var(--enlix-border); font-size: 14.5px; }
  .constancia-fila:last-child { border-bottom: none; }
  .constancia-fila .label { color: var(--enlix-muted); flex: none; }
  .constancia-fila .valor { text-align: right; color: var(--enlix-ink); }
  .constancia-bloque { margin-top: 18px; }
  .constancia-bloque .label { font-size: 13px; color: var(--enlix-muted); margin-bottom: 4px; }
  .constancia-bloque p { margin: 0; font-size: 14.5px; color: var(--enlix-ink); white-space: pre-line; }

  .constancia-acciones { margin-top: 24px; display: flex; gap: 12px; flex-wrap: wrap; }

  @media print {
    .enlix-navbar, .enlix-footer, .wa-float, .constancia-acciones { display: none !important; }
  }
</style>

<section class="section-pad">
  <div class="container constancia-col">

    <div class="constancia-ok">
      <span aria-hidden="true" style="font-size: 22px;">✓</span>
      <div>
        <strong>Tu {{ $reclamo->tipo->etiqueta() }} fue registrado.</strong>
        <div style="font-size: 13.5px;">Te enviamos una copia a {{ $reclamo->correo_consumidor }}.</div>
      </div>
    </div>

    <div class="constancia-card" id="constancia-imprimible">
      <p class="constancia-numero">N.° de {{ strtolower($reclamo->tipo->etiqueta()) }}<br><strong>{{ $reclamo->numero_correlativo }}</strong></p>

      <div class="constancia-fila"><span class="label">Fecha</span><span class="valor">{{ $reclamo->created_at->setTimezone('America/Lima')->translatedFormat('d \d\e F \d\e Y, H:i') }}</span></div>
      <div class="constancia-fila"><span class="label">Consumidor</span><span class="valor">{{ $reclamo->nombre_consumidor }} ({{ $reclamo->tipo_documento_consumidor }} {{ $reclamo->numero_documento_consumidor }})</span></div>
      <div class="constancia-fila"><span class="label">{{ $reclamo->tipo_bien->etiqueta() }}</span><span class="valor">{{ $reclamo->descripcion_bien }}</span></div>
      @if ($reclamo->monto_centimos)
        <div class="constancia-fila"><span class="label">Monto</span><span class="valor">S/ {{ number_format($reclamo->monto_centimos / 100, 2) }}</span></div>
      @endif
      <div class="constancia-fila"><span class="label">Plazo de respuesta</span><span class="valor">{{ $reclamo->fecha_limite_respuesta->translatedFormat('d \d\e F \d\e Y') }}</span></div>

      <div class="constancia-bloque">
        <p class="label">Detalle</p>
        <p>{{ $reclamo->detalle }}</p>
      </div>
      <div class="constancia-bloque">
        <p class="label">Pedido del consumidor</p>
        <p>{{ $reclamo->pedido_consumidor }}</p>
      </div>
    </div>

    <div class="constancia-acciones">
      <button type="button" class="btn btn-outline-enlix" onclick="window.print()">Imprimir constancia</button>
      <a href="{{ route('inicio') }}" class="btn btn-primary-enlix">Ir a Enlix</a>
    </div>

  </div>
</section>

@endsection
