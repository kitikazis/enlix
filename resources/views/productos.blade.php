@extends('layouts.app')

@section('content')

<style>
  /* ---------- Oferta del día (mobile-first) ---------- */
  /* La portada ocupa una sola pantalla: alto del viewport menos la navbar.
     Es min-height para que nunca recorte contenido; la foto absorbe el alto
     que sobra (fila 1fr) y se encoge primero en pantallas bajas. */
  .oferta-hero {
    --oferta-nav: 99px; /* alto de .enlix-navbar */
    position: relative;
    overflow: hidden;
    display: flex;
    min-height: calc(100vh - var(--oferta-nav));
    min-height: calc(100svh - var(--oferta-nav));
    padding: 14px 0 30px; /* 30px: deja el botón de compra sobre el WhatsApp flotante */
    background: linear-gradient(135deg, var(--enlix-blue-900) 0%, var(--enlix-blue-800) 100%);
    color: #fff;
  }
  .oferta-hero::before {
    /* Círculo decorativo de fondo, como en el diseño. */
    content: '';
    position: absolute;
    width: 720px; height: 720px;
    top: -360px; right: -240px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(201, 162, 74, .16) 0%, rgba(201, 162, 74, 0) 70%);
    pointer-events: none;
  }
  .oferta-hero > .container { display: flex; }
  .oferta-grid {
    position: relative;
    flex: 1;
    min-width: 0;
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    grid-template-rows: auto minmax(130px, 1fr) auto;
    grid-template-areas: "intro" "media" "cuerpo";
    gap: 12px;
  }
  .oferta-intro  { grid-area: intro; min-width: 0; }
  .oferta-media  { grid-area: media; min-width: 0; }
  .oferta-cuerpo { grid-area: cuerpo; min-width: 0; }

  .oferta-badge {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 4px 12px;
    border: 1px solid rgba(255, 255, 255, .28);
    border-radius: 999px;
    background: rgba(255, 255, 255, .08);
    color: #fff;
    font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
    white-space: nowrap;
  }
  .oferta-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--enlix-accent);
    box-shadow: 0 0 0 0 rgba(201, 162, 74, .6);
    animation: oferta-pulso 2s infinite;
  }
  @keyframes oferta-pulso {
    70%  { box-shadow: 0 0 0 8px rgba(201, 162, 74, 0); }
    100% { box-shadow: 0 0 0 0 rgba(201, 162, 74, 0); }
  }
  .oferta-titular {
    margin: 8px 0 0;
    color: #fff;
    font-size: clamp(1.75rem, 8vw, 2.5rem);
    font-weight: 800;
    line-height: 1.08;
    letter-spacing: -0.03em;
    overflow-wrap: break-word;
  }
  .oferta-titular-acento { display: block; color: var(--enlix-accent); }

  .oferta-producto { color: #fff; font-size: 17px; font-weight: 700; line-height: 1.25; margin: 0; }
  .oferta-subtitulo { color: #E5C77A; font-size: 14px; font-weight: 600; line-height: 1.35; margin: 2px 0 0; }
  /* Textos del admin muy largos no deben empujar el botón fuera de pantalla. */
  .oferta-producto, .oferta-subtitulo { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; }
  .oferta-bajada { color: rgba(255, 255, 255, .8); font-size: 14px; line-height: 1.5; margin: 8px 0 0; }

  /* Precio y contador comparten franja para ahorrar alto. */
  .oferta-compra {
    display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between;
    gap: 8px 16px;
    margin-top: 10px;
  }
  .oferta-precio { display: flex; flex-wrap: wrap; align-items: baseline; gap: 2px 10px; }
  .oferta-precio-actual { font-family: var(--font-heading); font-size: 30px; font-weight: 800; color: #fff; line-height: 1.1; white-space: nowrap; }
  .oferta-precio-actual .moneda { font-size: 17px; color: rgba(255, 255, 255, .75); margin-right: 2px; }
  .oferta-precio-antes { color: rgba(255, 255, 255, .75); text-decoration: line-through; font-size: 15px; }
  .oferta-descuento {
    align-self: center;
    padding: 2px 10px; border-radius: 999px;
    background: var(--enlix-accent); color: var(--enlix-blue-900);
    font-size: 13px; font-weight: 700;
  }
  .oferta-stock { font-size: 13px; font-weight: 600; color: #E5C77A; margin: 2px 0 0; }

  .oferta-contador-label { font-weight: 700; color: #fff; font-size: 12px; line-height: 1.3; margin: 0 0 4px; }
  .oferta-contador { display: flex; align-items: center; gap: 4px; }
  .oferta-contador-sep { font-weight: 700; color: rgba(255, 255, 255, .7); }
  .oferta-contador-caja {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    min-width: 48px; padding: 5px 4px 4px;
    background: #fff;
    border-radius: 8px;
  }
  .oferta-contador-num {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 21px; font-weight: 700; line-height: 1; color: var(--enlix-ink);
    font-variant-numeric: tabular-nums;
  }
  .oferta-contador-unidad { margin-top: 2px; font-size: 10px; font-weight: 600; line-height: 1.2; color: var(--enlix-muted); }
  .oferta-contador-nota { font-size: 12px; line-height: 1.35; color: rgba(255, 255, 255, .75); margin: 8px 0 0; }

  /* En móvil el enlace queda a la izquierda: a la derecha va el WhatsApp flotante. */
  .oferta-ctas { display: flex; flex-direction: column; align-items: stretch; margin-top: 10px; }
  .btn-oferta {
    min-height: 48px;
    padding: 10px 28px;
    border: 0; border-radius: 10px;
    background: var(--enlix-accent); color: var(--enlix-blue-900);
    font-weight: 700; font-size: 16px;
    transition: background-color .2s ease, transform .2s ease;
  }
  .btn-oferta:hover:not(:disabled) { background: #E5C77A; color: var(--enlix-blue-900); transform: translateY(-1px); }
  .btn-oferta:disabled { opacity: .6; cursor: not-allowed; }
  .oferta-link {
    display: inline-flex; align-items: center;
    align-self: flex-start;
    min-height: 44px;
    font-weight: 600; color: #fff; text-decoration: underline; text-underline-offset: 3px;
  }
  .oferta-link:hover { color: #E5C77A; }
  .oferta-error { font-size: 14px; color: #FFC9C9; margin: 4px 0 0; }

  /* Tarjeta blanca: las fotos del catálogo traen fondo blanco. */
  .oferta-figura {
    position: relative;
    height: 100%;
    margin: 0;
    overflow: hidden;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, .28);
  }
  .oferta-figura a { position: absolute; inset: 0; }
  .oferta-figura img {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    padding: 8px;
    object-fit: contain;
  }

  .oferta-eyebrow { font-size: 12px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--enlix-blue-600); margin: 0 0 8px; }
  .oferta-detalle { scroll-margin-top: 99px; /* alto de .enlix-navbar */ }
  .oferta-detalle-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 24px; }
  .oferta-detalle-nombre { font-size: clamp(1.5rem, 2vw + 1rem, 2rem); margin: 0 0 8px; }
  .oferta-detalle-desc { color: var(--enlix-text); }
  .oferta-detalle-desc p { margin: 0 0 .5em; }

  /* Comprar sin volver arriba: repite precio y tiempo restante de la portada. */
  .oferta-compra-card {
    padding: 22px;
    border-radius: 16px;
    background: linear-gradient(135deg, var(--enlix-blue-900) 0%, var(--enlix-blue-800) 100%);
    color: #fff;
    box-shadow: 0 16px 40px rgba(10, 37, 64, .22);
  }
  .oferta-compra-etiqueta { font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #E5C77A; margin: 0 0 6px; }
  .oferta-restante { font-size: 14px; color: rgba(255, 255, 255, .8); margin: 10px 0 0; }
  .oferta-restante strong {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    color: #fff;
    font-variant-numeric: tabular-nums;
  }
  .oferta-compra-card .btn-oferta { width: 100%; margin-top: 16px; }

  .oferta-beneficios { background: var(--enlix-bg-soft); }
  .oferta-beneficio {
    height: 100%;
    padding: 24px;
    background: #fff;
    border: 1px solid var(--enlix-border);
    border-radius: 16px;
  }
  .oferta-beneficio-num { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 13px; font-weight: 700; color: var(--enlix-blue-600); margin: 0 0 16px; }
  .oferta-beneficio h3 { font-size: 18px; margin: 0 0 6px; }
  .oferta-beneficio p:last-child { color: var(--enlix-muted); font-size: 15px; margin: 0; }

  /* Móvil bajo: el subtítulo y el badge ceden su alto a la foto. */
  @media (max-width: 991.98px) and (max-height: 720px) {
    .oferta-badge, .oferta-subtitulo { display: none; }
    .oferta-titular { margin-top: 0; }
  }
  /* Teléfonos de 320px: precio y contador siguen en una sola franja. */
  @media (max-width: 359.98px) {
    .oferta-hero { padding-top: 8px; }
    .oferta-grid { grid-template-rows: auto minmax(100px, 1fr) auto; gap: 8px; }
    .oferta-compra { flex-wrap: nowrap; column-gap: 8px; }
    .oferta-precio-actual { font-size: 24px; }
    .oferta-precio-actual .moneda { font-size: 14px; }
    .oferta-contador-caja { min-width: 42px; }
    .oferta-contador-num { font-size: 18px; }
  }
  @media (min-width: 576px) {
    .oferta-titular-acento { display: inline; }
    .oferta-ctas { flex-direction: row; align-items: center; gap: 20px; }
    .oferta-link { align-self: auto; }
  }
  @media (min-width: 992px) {
    .oferta-hero { padding: 20px 0 28px; }
    .oferta-grid {
      grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
      grid-template-rows: auto auto;
      grid-template-areas: "intro intro" "cuerpo media";
      column-gap: 48px; row-gap: 16px;
      align-items: center;
      align-content: center;
    }
    .oferta-intro { display: flex; flex-direction: row-reverse; align-items: center; justify-content: space-between; gap: 24px; }
    .oferta-titular { margin: 0; font-size: clamp(2.25rem, min(4.1vw, 9vh), 4rem); }
    .oferta-figura { width: 100%; height: auto; aspect-ratio: 1 / 1; max-height: calc(100vh - var(--oferta-nav) - 150px); max-height: calc(100svh - var(--oferta-nav) - 150px); }
    .oferta-figura img { padding: 12px; }
    .oferta-producto { font-size: 24px; }
    .oferta-subtitulo { font-size: 16px; margin-top: 4px; }
    .oferta-bajada { font-size: 15px; margin-top: 10px; }
    .oferta-compra { margin-top: 16px; }
    .oferta-precio-actual { font-size: 36px; }
    .oferta-contador-caja { min-width: 60px; padding: 7px 6px 5px; }
    .oferta-contador-num { font-size: 26px; }
    .oferta-contador-label { font-size: 13px; }
    .oferta-contador-nota { font-size: 13px; }
    .oferta-ctas { margin-top: 14px; }
    .btn-oferta { min-height: 52px; }
    .oferta-detalle-grid { grid-template-columns: minmax(0, 1fr) 360px; column-gap: 56px; align-items: start; }
    .oferta-compra-card { position: sticky; top: 115px; }
  }
  @media (prefers-reduced-motion: reduce) {
    .oferta-badge-dot { animation: none; }
    .btn-oferta:hover:not(:disabled) { transform: none; }
  }
</style>

@if ($oferta)
  @php
    $ofertaPrecioAntes = $oferta->precio_comparacion_centimos > $oferta->precio_centimos ? $oferta->precio_comparacion_centimos : null;
    $ofertaDescuento = $ofertaPrecioAntes ? (int) round((1 - $oferta->precio_centimos / $ofertaPrecioAntes) * 100) : null;
    $ofertaStock = $oferta->stockDisponible();
    $ofertaSpecs = $oferta->especificaciones ?? [];
    $ofertaSegundos = max(0, (int) now()->diffInSeconds($oferta->oferta_termina_en, false));
    $ofertaVence = $oferta->oferta_termina_en->setTimezone('America/Lima');
  @endphp

  <section class="oferta-hero" aria-labelledby="oferta-titular">
    <div class="container">
      <div class="oferta-grid">

        <div class="oferta-intro">
          <span class="oferta-badge"><span class="oferta-badge-dot" aria-hidden="true"></span>Oferta especial · 24 horas</span>
          <h1 id="oferta-titular" class="oferta-titular"><span class="oferta-titular-acento">¡Oferta,</span> aprovecha ahora!</h1>
        </div>

        <div class="oferta-media">
          <figure class="oferta-figura">
            {{-- La ficha de la oferta está en esta misma página. --}}
            <a href="#oferta-detalle" aria-label="Ver la ficha técnica de {{ $oferta->nombre }}">
              <img src="{{ $oferta->imagen_url }}" alt="{{ $oferta->nombre }}" width="800" height="800" fetchpriority="high">
            </a>
          </figure>
        </div>

        <div class="oferta-cuerpo" data-oferta-zona>
          <h2 class="oferta-producto">{{ $oferta->nombre }}</h2>
          @if ($oferta->oferta_titular)
            <p class="oferta-subtitulo">{{ $oferta->oferta_titular }}</p>
          @endif
          {{-- En móvil no cabe en la portada: se muestra abajo, en #oferta-detalle. --}}
          @if ($oferta->oferta_bajada)
            <p class="oferta-bajada d-none d-lg-block">{{ $oferta->oferta_bajada }}</p>
          @endif

          <div class="oferta-compra">
            <div>
              <div class="oferta-precio">
                <span class="oferta-precio-actual"><span class="moneda">S/</span>{{ number_format($oferta->precio_centimos / 100, 2) }}</span>
                @if ($ofertaPrecioAntes)
                  <span class="oferta-precio-antes"><span class="visually-hidden">Precio regular:</span> S/ {{ number_format($ofertaPrecioAntes / 100, 2) }}</span>
                  @if ($ofertaDescuento > 0)
                    <span class="oferta-descuento">-{{ $ofertaDescuento }}%</span>
                  @endif
                @endif
              </div>
              @if ($ofertaStock > 0 && $ofertaStock <= $oferta->umbral_stock_bajo)
                <p class="oferta-stock">{{ $ofertaStock === 1 ? '¡Última unidad disponible!' : "¡Últimas {$ofertaStock} unidades!" }}</p>
              @endif
            </div>

            <div data-oferta-contador data-segundos="{{ $ofertaSegundos }}">
              <p class="oferta-contador-label" data-oferta-contador-label>La oferta termina en</p>
              <div class="oferta-contador" role="timer" aria-label="Tiempo restante de la oferta">
                <div class="oferta-contador-caja"><span class="oferta-contador-num" data-unidad="horas">{{ str_pad((string) intdiv($ofertaSegundos, 3600), 2, '0', STR_PAD_LEFT) }}</span><span class="oferta-contador-unidad">Horas</span></div>
                <span class="oferta-contador-sep" aria-hidden="true">:</span>
                <div class="oferta-contador-caja"><span class="oferta-contador-num" data-unidad="minutos">{{ str_pad((string) intdiv($ofertaSegundos % 3600, 60), 2, '0', STR_PAD_LEFT) }}</span><span class="oferta-contador-unidad">Min</span></div>
                <span class="oferta-contador-sep" aria-hidden="true">:</span>
                <div class="oferta-contador-caja"><span class="oferta-contador-num" data-unidad="segundos">{{ str_pad((string) ($ofertaSegundos % 60), 2, '0', STR_PAD_LEFT) }}</span><span class="oferta-contador-unidad">Seg</span></div>
              </div>
            </div>
          </div>
          <p class="oferta-contador-nota">Válida hasta el {{ $ofertaVence->translatedFormat('j \d\e F') }} a las {{ $ofertaVence->format('H:i') }} (hora de Perú) o hasta agotar stock.</p>

          <div class="oferta-ctas">
            @if ($ofertaStock > 0)
              <button type="button" class="btn-oferta" data-oferta-comprar data-producto-id="{{ $oferta->id }}">Comprar ahora</button>
            @else
              <button type="button" class="btn-oferta" disabled>Agotado</button>
            @endif
            <a href="#oferta-detalle" class="oferta-link">{{ $ofertaSpecs ? 'Ver especificaciones' : 'Ver detalles' }}</a>
          </div>
          <p class="oferta-error" data-oferta-error role="alert" hidden></p>
        </div>

      </div>
    </div>
  </section>

  <section id="oferta-detalle" class="oferta-detalle section-pad-sm" aria-labelledby="oferta-detalle-nombre">
    <div class="container">
      <div class="oferta-detalle-grid">

        <div>
          <p class="oferta-eyebrow">Ficha técnica</p>
          <h2 id="oferta-detalle-nombre" class="oferta-detalle-nombre">{{ $oferta->nombre }}</h2>
          @if ($oferta->oferta_bajada)
            <p class="oferta-detalle-desc d-lg-none">{{ $oferta->oferta_bajada }}</p>
          @endif
          {{-- descripcion ya viene saneada por HTMLPurifier (ver Producto::descripcion()). --}}
          <div class="oferta-detalle-desc">{!! $oferta->descripcion !!}</div>

          @if ($ofertaSpecs)
            <dl class="oferta-specs">
              @foreach ($ofertaSpecs as $spec)
                <div class="oferta-spec">
                  <dt>{{ $spec['clave'] }}</dt>
                  <dd>{{ $spec['valor'] }}</dd>
                </div>
              @endforeach
            </dl>
          @endif
        </div>

        <aside class="oferta-compra-card" data-oferta-zona aria-label="Comprar {{ $oferta->nombre }}">
          <p class="oferta-compra-etiqueta">Precio de oferta</p>
          <div class="oferta-precio">
            <span class="oferta-precio-actual"><span class="moneda">S/</span>{{ number_format($oferta->precio_centimos / 100, 2) }}</span>
            @if ($ofertaPrecioAntes)
              <span class="oferta-precio-antes"><span class="visually-hidden">Precio regular:</span> S/ {{ number_format($ofertaPrecioAntes / 100, 2) }}</span>
              @if ($ofertaDescuento > 0)
                <span class="oferta-descuento">-{{ $ofertaDescuento }}%</span>
              @endif
            @endif
          </div>
          @if ($ofertaStock > 0 && $ofertaStock <= $oferta->umbral_stock_bajo)
            <p class="oferta-stock">{{ $ofertaStock === 1 ? '¡Última unidad disponible!' : "¡Últimas {$ofertaStock} unidades!" }}</p>
          @endif
          <p class="oferta-restante">Termina en <strong data-oferta-restante>{{ sprintf('%02d:%02d:%02d', intdiv($ofertaSegundos, 3600), intdiv($ofertaSegundos % 3600, 60), $ofertaSegundos % 60) }}</strong></p>
          @if ($ofertaStock > 0)
            <button type="button" class="btn-oferta" data-oferta-comprar data-producto-id="{{ $oferta->id }}">Comprar ahora</button>
          @else
            <button type="button" class="btn-oferta" disabled>Agotado</button>
          @endif
          <p class="oferta-error" data-oferta-error role="alert" hidden></p>
        </aside>

      </div>
    </div>
  </section>

  @if ($oferta->oferta_beneficios)
    <section class="oferta-beneficios section-pad-sm" aria-labelledby="oferta-beneficios-titulo">
      <div class="container">
        <h2 id="oferta-beneficios-titulo" class="mb-4">Una experiencia a otro nivel</h2>
        <div class="row g-3 g-lg-4">
          @foreach ($oferta->oferta_beneficios as $i => $beneficio)
            <div class="col-md-4">
              <div class="oferta-beneficio">
                <p class="oferta-beneficio-num" aria-hidden="true">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }} /</p>
                <h3>{{ $beneficio['titulo'] }}</h3>
                @if ($beneficio['texto'] !== '')
                  <p>{{ $beneficio['texto'] }}</p>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>
  @endif
@endif

@if (! $oferta || count($productos))
<section class="section-pad">
  <div class="container">

    <div class="text-center mb-5">
      <p class="svc-block-num">Tienda</p>
      @if ($oferta)
        <h2>Más productos</h2>
      @else
        <h1>Nuestros productos</h1>
      @endif
      <p style="color: var(--enlix-muted); max-width: 640px; margin: 0 auto;">
        Explora nuestro catálogo y agrega lo que necesites a tu carrito.
      </p>
    </div>

    <div class="row g-4 justify-content-center">
      @foreach ($productos as $slug => $p)
        <div class="col-md-6 col-lg-4">
          @include('partials.producto-card', ['p' => $p])
        </div>
      @endforeach
    </div>

  </div>
</section>
@endif

@include('partials.tienda-scripts')

@push('scripts')
<script nonce="{{ $cspNonce }}">
  // Contador de la oferta del día. Cuenta los segundos que calculó el
  // servidor (no la hora del reloj del visitante, que puede estar mal) hacia
  // el vencimiento REAL: es el mismo para todos y no se reinicia al recargar.
  (function () {
    var contador = document.querySelector('[data-oferta-contador]');
    if (!contador) return;

    var fin = Date.now() + parseInt(contador.dataset.segundos, 10) * 1000;
    var cajas = {
      horas: contador.querySelector('[data-unidad="horas"]'),
      minutos: contador.querySelector('[data-unidad="minutos"]'),
      segundos: contador.querySelector('[data-unidad="segundos"]'),
    };
    var restanteTexto = document.querySelector('[data-oferta-restante]');
    var dos = function (n) { return String(n).padStart(2, '0'); };
    var timer;

    function pintar() {
      var restante = Math.max(0, Math.round((fin - Date.now()) / 1000));
      cajas.horas.textContent = dos(Math.floor(restante / 3600));
      cajas.minutos.textContent = dos(Math.floor((restante % 3600) / 60));
      cajas.segundos.textContent = dos(restante % 60);
      if (restanteTexto) {
        restanteTexto.textContent = cajas.horas.textContent + ':' + cajas.minutos.textContent + ':' + cajas.segundos.textContent;
      }

      if (restante === 0) {
        clearInterval(timer);
        contador.querySelector('[data-oferta-contador-label]').textContent = 'La oferta terminó';
        document.querySelectorAll('[data-oferta-comprar]').forEach(function (comprar) {
          comprar.disabled = true;
          comprar.textContent = 'Oferta finalizada';
        });
        // Unos segundos de margen para que el servidor ya la dé por vencida
        // al recargar y muestre el catálogo normal (o la oferta nueva).
        setTimeout(function () { window.location.reload(); }, 3000);
      }
    }

    pintar();
    timer = setInterval(pintar, 1000);
  })();
</script>
@endpush

@endsection
