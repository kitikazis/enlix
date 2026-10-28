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
  .prod-card-img {
    width: calc(100% + 56px);
    margin: -28px -28px 20px -28px;
    height: 220px;
    /* contain y no cover: la foto del producto se ve completa, sin cortar
       la laptop/caja arriba y abajo. */
    object-fit: contain;
    padding: 16px;
    border-radius: 8px 8px 0 0;
    background: var(--enlix-bg-soft);
  }
  .prod-card-title { font-size: 20px; margin: 0 0 8px; color: var(--enlix-ink); }
  .prod-card-desc  { color: var(--enlix-muted); font-size: 14px; line-height: 1.6; margin: 0; }
  .prod-card-desc p { margin: 0 0 0.5em; }
  .prod-card-desc p:last-child { margin-bottom: 0; }
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

  /* ---------- Oferta del día (mobile-first) ---------- */
  .oferta-hero {
    position: relative;
    overflow: hidden;
    padding: 32px 0 48px;
    background: linear-gradient(180deg, var(--enlix-bg-soft) 0%, #fff 100%);
  }
  .oferta-hero::before {
    /* Círculo decorativo de fondo, como en el diseño. */
    content: '';
    position: absolute;
    width: 720px; height: 720px;
    top: -360px; right: -240px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(45, 116, 196, .10) 0%, rgba(45, 116, 196, 0) 70%);
    pointer-events: none;
  }
  .oferta-grid {
    position: relative;
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    grid-template-areas: "intro" "media" "cuerpo";
    gap: 24px;
  }
  .oferta-intro  { grid-area: intro; min-width: 0; }
  .oferta-media  { grid-area: media; min-width: 0; }
  .oferta-cuerpo { grid-area: cuerpo; min-width: 0; }

  .oferta-badge {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 14px;
    border: 1px solid #C9DAF0;
    border-radius: 999px;
    background: var(--enlix-blue-50);
    color: var(--enlix-blue-600);
    font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
  }
  .oferta-badge-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--enlix-blue-600);
    box-shadow: 0 0 0 0 rgba(30, 93, 168, .5);
    animation: oferta-pulso 2s infinite;
  }
  @keyframes oferta-pulso {
    70%  { box-shadow: 0 0 0 8px rgba(30, 93, 168, 0); }
    100% { box-shadow: 0 0 0 0 rgba(30, 93, 168, 0); }
  }
  .oferta-titular {
    margin: 16px 0 0;
    font-size: clamp(2.125rem, 4vw + 1.25rem, 4rem);
    font-weight: 800;
    line-height: 1.08;
    letter-spacing: -0.03em;
    overflow-wrap: break-word;
  }
  .oferta-bajada { color: var(--enlix-muted); font-size: 16px; max-width: 520px; margin: 0 0 20px; }

  .oferta-precio { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 12px; margin-bottom: 4px; }
  .oferta-precio-actual { font-family: var(--font-heading); font-size: 32px; font-weight: 800; color: var(--enlix-ink); line-height: 1.1; }
  .oferta-precio-actual .moneda { font-size: 18px; color: var(--enlix-muted); margin-right: 2px; }
  .oferta-precio-antes { color: var(--enlix-muted); text-decoration: line-through; font-size: 16px; }
  .oferta-descuento {
    align-self: center;
    padding: 2px 10px; border-radius: 999px;
    background: var(--enlix-blue-900); color: #fff;
    font-size: 13px; font-weight: 700;
  }
  .oferta-stock { font-size: 14px; font-weight: 600; color: var(--enlix-accent-ink); margin: 4px 0 0; }

  .oferta-contador-label { font-weight: 700; color: var(--enlix-ink); font-size: 15px; margin: 24px 0 10px; }
  .oferta-contador { display: flex; align-items: center; gap: 8px; }
  .oferta-contador-sep { font-weight: 700; color: var(--enlix-muted); }
  .oferta-contador-caja {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    min-width: 72px; padding: 12px 8px 10px;
    background: #fff;
    border: 1px solid var(--enlix-border);
    border-radius: 12px;
    box-shadow: 0 4px 14px rgba(10, 37, 64, .05);
  }
  .oferta-contador-num {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 30px; font-weight: 700; line-height: 1; color: var(--enlix-ink);
    font-variant-numeric: tabular-nums;
  }
  .oferta-contador-unidad { margin-top: 6px; font-size: 10px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--enlix-muted); }
  .oferta-contador-nota { font-size: 13px; color: var(--enlix-muted); margin: 10px 0 0; }

  .oferta-ctas { display: flex; flex-direction: column; align-items: stretch; gap: 8px; margin-top: 28px; }
  .btn-oferta {
    min-height: 52px;
    padding: 12px 28px;
    border: 0; border-radius: 10px;
    background: var(--enlix-blue-900); color: #fff;
    font-weight: 700; font-size: 16px;
    transition: background-color .2s ease, transform .2s ease;
  }
  .btn-oferta:hover:not(:disabled) { background: var(--enlix-blue-800); color: #fff; transform: translateY(-1px); }
  .btn-oferta:disabled { opacity: .6; cursor: not-allowed; }
  .oferta-link {
    display: inline-flex; align-items: center; justify-content: center;
    min-height: 44px; padding: 0 12px;
    font-weight: 600; color: var(--enlix-blue-600); text-decoration: none;
  }
  .oferta-link:hover { color: var(--enlix-blue-800); text-decoration: underline; }
  .oferta-error { font-size: 14px; margin: 8px 0 0; }

  .oferta-figura { position: relative; margin: 0; }
  .oferta-figura img {
    display: block; width: 100%;
    aspect-ratio: 4 / 3;
    object-fit: contain;
    filter: drop-shadow(0 24px 32px rgba(10, 37, 64, .18));
  }
  .oferta-figura figcaption {
    position: absolute; right: 4%; bottom: 4%;
    max-width: 80%;
    padding: 10px 16px;
    background: #fff;
    border: 1px solid var(--enlix-border);
    border-radius: 10px;
    box-shadow: 0 10px 24px rgba(10, 37, 64, .10);
    color: var(--enlix-blue-600);
    font-size: 12px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
    overflow-wrap: break-word;
  }

  .oferta-eyebrow { font-size: 12px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--enlix-blue-600); margin: 0 0 8px; }
  .oferta-detalle-nombre { font-size: clamp(1.5rem, 2vw + 1rem, 2rem); margin: 0 0 8px; }
  .oferta-detalle-desc { color: var(--enlix-muted); max-width: 760px; }
  .oferta-detalle-desc p { margin: 0 0 .5em; }
  .oferta-specs {
    display: grid; grid-template-columns: repeat(2, minmax(0, 1fr));
    margin: 24px 0 0; padding: 0;
    background: #fff;
    border: 1px solid var(--enlix-border);
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(10, 37, 64, .05);
    overflow: hidden;
  }
  .oferta-spec { padding: 18px 20px; min-width: 0; border-color: var(--enlix-border); border-style: solid; border-width: 0; }
  .oferta-spec:nth-child(odd) { border-right-width: 1px; }
  .oferta-spec:nth-child(n+3) { border-top-width: 1px; }
  .oferta-spec dt { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--enlix-muted); margin-bottom: 6px; }
  .oferta-spec dd { margin: 0; font-weight: 700; color: var(--enlix-ink); overflow-wrap: break-word; }
  .oferta-specs-todas { margin-top: 16px; }
  .oferta-specs-todas summary { display: inline-flex; align-items: center; min-height: 44px; cursor: pointer; font-weight: 600; color: var(--enlix-blue-600); }
  .oferta-specs-tabla { width: 100%; max-width: 760px; font-size: 14px; }
  .oferta-specs-tabla th, .oferta-specs-tabla td { padding: 10px 12px; border-bottom: 1px solid var(--enlix-border); vertical-align: top; overflow-wrap: anywhere; }
  .oferta-specs-tabla th { width: 40%; color: var(--enlix-muted); font-weight: 600; }

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

  @media (min-width: 576px) {
    .oferta-ctas { flex-direction: row; align-items: center; gap: 16px; }
    .oferta-contador-caja { min-width: 84px; }
    .oferta-contador-num { font-size: 34px; }
  }
  @media (min-width: 992px) {
    .oferta-hero { padding: 72px 0 88px; }
    .oferta-grid {
      grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr);
      grid-template-areas: "intro media" "cuerpo media";
      grid-template-rows: auto 1fr;
      column-gap: 48px; row-gap: 20px;
      align-items: start;
    }
    .oferta-media { align-self: center; }
    .oferta-bajada { font-size: 17px; }
    .oferta-specs { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .oferta-spec:nth-child(odd) { border-right-width: 0; }
    .oferta-spec:nth-child(n+3) { border-top-width: 0; }
    .oferta-spec:not(:last-child) { border-right-width: 1px; }
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
          <h1 id="oferta-titular" class="oferta-titular">{{ $oferta->oferta_titular ?: $oferta->nombre }}</h1>
        </div>

        <div class="oferta-media">
          <figure class="oferta-figura">
            <img src="{{ $oferta->imagen_url }}" alt="{{ $oferta->nombre }}" width="1200" height="900" fetchpriority="high">
            <figcaption>{{ $oferta->nombre }}</figcaption>
          </figure>
        </div>

        <div class="oferta-cuerpo">
          @if ($oferta->oferta_bajada)
            <p class="oferta-bajada">{{ $oferta->oferta_bajada }}</p>
          @endif

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

          <div data-oferta-contador data-segundos="{{ $ofertaSegundos }}">
            <p class="oferta-contador-label" data-oferta-contador-label>La oferta termina en</p>
            <div class="oferta-contador" role="timer" aria-label="Tiempo restante de la oferta">
              <div class="oferta-contador-caja"><span class="oferta-contador-num" data-unidad="horas">{{ str_pad((string) intdiv($ofertaSegundos, 3600), 2, '0', STR_PAD_LEFT) }}</span><span class="oferta-contador-unidad">Horas</span></div>
              <span class="oferta-contador-sep" aria-hidden="true">:</span>
              <div class="oferta-contador-caja"><span class="oferta-contador-num" data-unidad="minutos">{{ str_pad((string) intdiv($ofertaSegundos % 3600, 60), 2, '0', STR_PAD_LEFT) }}</span><span class="oferta-contador-unidad">Minutos</span></div>
              <span class="oferta-contador-sep" aria-hidden="true">:</span>
              <div class="oferta-contador-caja"><span class="oferta-contador-num" data-unidad="segundos">{{ str_pad((string) ($ofertaSegundos % 60), 2, '0', STR_PAD_LEFT) }}</span><span class="oferta-contador-unidad">Segundos</span></div>
            </div>
            <p class="oferta-contador-nota">Válida hasta el {{ $ofertaVence->translatedFormat('j \d\e F') }} a las {{ $ofertaVence->format('H:i') }} (hora de Perú) o hasta agotar stock.</p>
          </div>

          <div class="oferta-ctas">
            @if ($ofertaStock > 0)
              <button type="button" class="btn-oferta" data-oferta-comprar data-producto-id="{{ $oferta->id }}">Comprar ahora</button>
            @else
              <button type="button" class="btn-oferta" disabled>Agotado</button>
            @endif
            <a href="#oferta-detalle" class="oferta-link">{{ $ofertaSpecs ? 'Ver especificaciones' : 'Ver detalles' }}</a>
          </div>
          <p class="oferta-error text-danger" data-oferta-error role="alert" hidden></p>
        </div>

      </div>
    </div>
  </section>

  <section id="oferta-detalle" class="section-pad-sm" aria-labelledby="oferta-detalle-nombre" style="scroll-margin-top: 80px;">
    <div class="container">
      <p class="oferta-eyebrow">El protagonista de la oferta</p>
      <h2 id="oferta-detalle-nombre" class="oferta-detalle-nombre">{{ $oferta->nombre }}</h2>
      {{-- descripcion ya viene saneada por HTMLPurifier (ver Producto::descripcion()). --}}
      <div class="oferta-detalle-desc">{!! $oferta->descripcion !!}</div>

      @if ($ofertaSpecs)
        <dl class="oferta-specs">
          @foreach (array_slice($ofertaSpecs, 0, 4) as $spec)
            <div class="oferta-spec">
              <dt>{{ $spec['clave'] }}</dt>
              <dd>{{ $spec['valor'] }}</dd>
            </div>
          @endforeach
        </dl>

        @if (count($ofertaSpecs) > 4)
          <details class="oferta-specs-todas">
            <summary>Ver todas las especificaciones ({{ count($ofertaSpecs) }})</summary>
            <table class="oferta-specs-tabla">
              <tbody>
                @foreach ($ofertaSpecs as $spec)
                  <tr><th scope="row">{{ $spec['clave'] }}</th><td>{{ $spec['valor'] }}</td></tr>
                @endforeach
              </tbody>
            </table>
          </details>
        @endif
      @endif
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
          <div class="prod-card">
            <img src="{{ $p['imagen_url'] }}" alt="{{ $p['nombre'] }}" class="prod-card-img" loading="lazy">
            <h3 class="prod-card-title">{{ $p['nombre'] }}</h3>
            {{-- descripcion ya pasa por HTMLPurifier al guardarse (ver
                 Producto::descripcion() en el modelo, solo permite
                 p/br/strong/b/em/i/u/ul/ol/li/a), por eso es seguro
                 mostrarla como HTML en vez de texto escapado. --}}
            <div class="prod-card-desc">{!! $p['descripcion'] !!}</div>

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
                      data-producto-id="{{ $p['id'] }}"
                      data-imagen-url="{{ $p['imagen_url'] }}"
                      @if ($p['precio_comparacion_centimos']) data-precio-comparacion-centimos="{{ $p['precio_comparacion_centimos'] }}" @endif>
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
@endif

@push('scripts')
<script nonce="{{ $cspNonce }}">
  const CSRF_TOKEN = @json(csrf_token());
  const URL_AGREGAR_CARRITO = @json(route('carrito.items.store'));
  const URL_RESUMEN_CARRITO = @json(route('carrito.resumen'));
  const URL_CHECKOUT = @json(route('checkout.index'));

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
    var dos = function (n) { return String(n).padStart(2, '0'); };
    var timer;

    function pintar() {
      var restante = Math.max(0, Math.round((fin - Date.now()) / 1000));
      cajas.horas.textContent = dos(Math.floor(restante / 3600));
      cajas.minutos.textContent = dos(Math.floor((restante % 3600) / 60));
      cajas.segundos.textContent = dos(restante % 60);

      if (restante === 0) {
        clearInterval(timer);
        contador.querySelector('[data-oferta-contador-label]').textContent = 'La oferta terminó';
        var comprar = document.querySelector('[data-oferta-comprar]');
        if (comprar) {
          comprar.disabled = true;
          comprar.textContent = 'Oferta finalizada';
        }
        // Unos segundos de margen para que el servidor ya la dé por vencida
        // al recargar y muestre el catálogo normal (o la oferta nueva).
        setTimeout(function () { window.location.reload(); }, 3000);
      }
    }

    pintar();
    timer = setInterval(pintar, 1000);
  })();

  // "Comprar ahora": agrega la oferta al carrito (si no estaba ya) y va
  // directo al checkout SOLO con ese item (?items=), sin arrastrar lo que
  // hubiera antes en el carrito.
  (function () {
    var btn = document.querySelector('[data-oferta-comprar]');
    if (!btn) return;

    var error = document.querySelector('[data-oferta-error]');
    var textoOriginal = btn.textContent.trim();
    var productoId = parseInt(btn.dataset.productoId, 10);

    function irAlCheckout(itemId) {
      window.location.href = URL_CHECKOUT + '?items=' + encodeURIComponent(itemId);
    }

    function fallar(mensaje) {
      error.textContent = mensaje;
      error.hidden = false;
      btn.disabled = false;
      btn.removeAttribute('aria-busy');
      btn.textContent = textoOriginal;
    }

    function itemDeOferta(data) {
      return (data.items || []).find(function (i) { return i.producto_id === productoId; });
    }

    btn.addEventListener('click', function () {
      if (btn.disabled) return;

      btn.disabled = true;
      btn.setAttribute('aria-busy', 'true');
      btn.textContent = 'Procesando…';
      error.hidden = true;

      fetchJsonEnlix('GET', URL_RESUMEN_CARRITO, null, CSRF_TOKEN).then(function (resumen) {
        var existente = itemDeOferta(resumen);
        if (existente) {
          irAlCheckout(existente.id);
          return;
        }

        return fetchJsonEnlix('POST', URL_AGREGAR_CARRITO, { producto_id: productoId, cantidad: 1 }, CSRF_TOKEN).then(function (data) {
          if (!data.ok) {
            fallar(data.mensaje || 'No se pudo agregar al carrito.');
            return;
          }
          actualizarBadgeCarrito(data.cantidad_total);
          var item = itemDeOferta(data);
          if (item) {
            irAlCheckout(item.id);
          } else {
            window.location.href = URL_CHECKOUT;
          }
        });
      }).catch(function () {
        refrescarBadgeCarrito();
        fallar('No se pudo procesar. Revisa tu carrito e inténtalo de nuevo.');
      });
    });
  })();

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

        // El modal (ver initModalCarrito() en enlix.js) solo se abre si
        // encontramos el item recien agregado en la respuesta del servidor
        // - nunca antes de confirmar el OK.
        var productoId = parseInt(btn.dataset.productoId, 10);
        var item = data.items.find(function (i) { return i.producto_id === productoId; });
        if (item) {
          window.dispatchEvent(new CustomEvent('cart:added', {
            detail: {
              itemId: item.id,
              nombre: item.nombre,
              imagenUrl: btn.dataset.imagenUrl,
              precioUnitarioCentimos: item.precio_unitario_centimos,
              precioComparacionCentimos: btn.dataset.precioComparacionCentimos
                ? parseInt(btn.dataset.precioComparacionCentimos, 10)
                : null,
              cantidad: item.cantidad,
              stockDisponible: item.stock_disponible,
              botonOrigen: btn,
            },
          }));
        }

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
