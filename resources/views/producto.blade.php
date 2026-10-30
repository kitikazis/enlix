@extends('layouts.app')

@section('content')

<style>
  /* ---------- Detalle de producto (mobile-first) ---------- */
  .pd-migas { margin-bottom: 16px; font-size: 13px; }
  .pd-migas .breadcrumb { flex-wrap: nowrap; margin: 0; }
  .pd-migas a { color: var(--enlix-muted); text-decoration: none; white-space: nowrap; }
  .pd-migas a:hover { color: var(--enlix-blue-600); text-decoration: underline; }
  .pd-migas .breadcrumb-item.active { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--enlix-ink); }

  .pd-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 24px; }

  /* Galería: en móvil la foto va arriba y las miniaturas debajo, en fila. */
  .pd-galeria { display: grid; grid-template-columns: minmax(0, 1fr); gap: 10px; min-width: 0; }
  /* Tarjeta blanca: las fotos del catálogo traen fondo blanco. */
  .pd-foto {
    position: relative;
    display: flex; align-items: center; justify-content: center;
    aspect-ratio: 1 / 1;
    max-height: 62vh;
    width: 100%;
    margin: 0;
    padding: 16px;
    background: #fff;
    border: 1px solid var(--enlix-border);
    border-radius: 12px;
    overflow: hidden;
    touch-action: pan-y; /* deslizar en horizontal cambia de foto */
  }
  .pd-foto img { width: 100%; height: 100%; object-fit: contain; }
  .pd-flecha {
    position: absolute; top: 50%;
    display: flex; align-items: center; justify-content: center;
    width: 44px; height: 44px;
    border: 0; border-radius: 50%;
    background: rgba(10, 37, 64, .85);
    color: #fff;
    transform: translateY(-50%);
    transition: background-color .2s ease;
  }
  .pd-flecha:hover { background: var(--enlix-blue-900); }
  .pd-flecha--anterior { left: 10px; }
  .pd-flecha--siguiente { right: 10px; }
  .pd-miniaturas { position: relative; display: flex; gap: 8px; padding: 2px; overflow-x: auto; scrollbar-width: none; }
  .pd-miniaturas::-webkit-scrollbar { display: none; }
  .pd-miniatura {
    flex: none;
    width: 64px; height: 64px;
    padding: 4px;
    background: #fff;
    border: 1px solid var(--enlix-border);
    border-radius: 8px;
    transition: border-color .2s ease;
  }
  .pd-miniatura img { width: 100%; height: 100%; object-fit: contain; }
  .pd-miniatura:hover { border-color: var(--enlix-blue-500); }
  .pd-miniatura.is-activa { border-color: var(--enlix-blue-800); box-shadow: 0 0 0 1px var(--enlix-blue-800); }

  .pd-info { min-width: 0; }
  .pd-marca { margin: 0 0 6px; font-family: var(--font-heading); font-size: 16px; font-weight: 700; color: var(--enlix-blue-800); }
  .pd-nombre { margin: 0; font-size: clamp(1.4rem, 1.5vw + 1rem, 1.9rem); line-height: 1.2; overflow-wrap: break-word; }

  .pd-claves {
    margin: 16px 0 0;
    padding: 12px 16px 12px 32px;
    background: #fff;
    border: 1px solid var(--enlix-border);
    border-radius: 10px;
    font-size: 14px;
    color: var(--enlix-ink);
  }
  .pd-claves li { padding: 2px 0; overflow-wrap: break-word; }
  .pd-claves li::marker { color: var(--enlix-blue-600); }

  .pd-ref { margin: 16px 0 0; font-size: 14px; color: var(--enlix-muted); overflow-wrap: break-word; }

  .pd-precio { display: flex; flex-wrap: wrap; align-items: baseline; gap: 2px 10px; margin-top: 8px; }
  .pd-precio-actual { font-family: var(--font-heading); font-size: 36px; font-weight: 800; line-height: 1.1; color: var(--enlix-blue-800); white-space: nowrap; }
  .pd-precio-actual .moneda { margin-right: 4px; font-size: 24px; }
  .pd-descuento {
    align-self: center;
    padding: 2px 10px;
    border-radius: 999px;
    background: var(--enlix-accent);
    color: var(--enlix-blue-900);
    font-size: 13px; font-weight: 700;
  }
  .pd-precio-regular { margin: 4px 0 0; font-size: 15px; color: var(--enlix-muted); }

  .pd-stock {
    display: inline-flex; align-items: center; gap: 8px;
    margin: 12px 0 0; padding: 8px 0;
    border-block: 1px solid var(--enlix-border);
    font-size: 14px; font-weight: 600;
  }
  .pd-stock::before { content: ''; flex: none; width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
  .pd-stock--ok { color: #1B7F4B; }
  .pd-stock--bajo { color: var(--enlix-accent-ink); }
  .pd-stock--agotado { color: #B42318; }

  .pd-cantidad-label { display: block; margin: 16px 0 6px; font-size: 14px; font-weight: 500; color: var(--enlix-muted); }
  .pd-compra { display: flex; gap: 10px; }
  .pd-compra > .pd-cta { flex: 1 1 0; min-width: 0; }
  .pd-stepper {
    flex: none;
    display: inline-flex; align-items: center;
    height: 48px;
    border: 1px solid var(--enlix-border);
    border-radius: var(--radius-sm);
    background: #fff;
  }
  .pd-stepper button {
    width: 40px; height: 100%;
    border: 0;
    background: none;
    color: var(--enlix-blue-800);
    font-size: 20px; line-height: 1;
  }
  .pd-stepper button:disabled { color: var(--enlix-border); }
  .pd-stepper span { min-width: 26px; font-weight: 600; text-align: center; font-variant-numeric: tabular-nums; }
  .pd-cta { min-height: 48px; font-size: 16px; font-weight: 600; }
  .pd-cta--ancho { width: 100%; margin-top: 10px; }

  .pd-ventajas { margin: 20px 0 0; padding: 14px 16px; list-style: none; background: var(--enlix-bg-soft); border-radius: 10px; }
  .pd-ventajas li { position: relative; padding: 4px 0 4px 24px; font-size: 14px; color: var(--enlix-ink); }
  .pd-ventajas li::before { content: '✓'; position: absolute; left: 0; font-weight: 700; color: var(--enlix-blue-900); }
  .pd-whatsapp { display: inline-flex; align-items: center; min-height: 44px; margin-top: 6px; font-size: 14px; font-weight: 600; color: var(--enlix-blue-800); }

  .pd-detalle { background: var(--enlix-bg-soft); }
  .pd-detalle-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 32px; }
  .pd-seccion-titulo { margin: 0 0 14px; font-size: 22px; }
  .pd-descripcion { max-width: 70ch; color: var(--enlix-text); overflow-wrap: break-word; }
  .pd-descripcion p { margin: 0 0 .75em; }
  .pd-descripcion p:last-child { margin-bottom: 0; }
  .pd-detalle .oferta-specs { margin-top: 0; }

  @media (min-width: 992px) {
    .pd-migas { margin-bottom: 24px; }
    .pd-grid { grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); column-gap: 40px; align-items: start; }
    /* Miniaturas en columna a la izquierda de la foto. */
    .pd-galeria { position: sticky; top: 115px; }
    .pd-galeria--varias { grid-template-columns: 80px minmax(0, 1fr); gap: 12px; }
    .pd-galeria--varias .pd-miniaturas { order: -1; flex-direction: column; max-height: calc(100vh - 140px); overflow: hidden auto; }
    .pd-miniatura { width: 72px; height: 72px; }
    .pd-foto { max-height: calc(100vh - 140px); padding: 24px; }
    .pd-precio-actual { font-size: 42px; }
    .pd-detalle-grid--doble { grid-template-columns: minmax(0, 3fr) minmax(0, 2fr); column-gap: 56px; }
  }
</style>

@php
  $producto = $productoDetalle;
  $fotos = $producto->fotos();
  $precioAntes = $producto->precio_comparacion_centimos > $producto->precio_centimos ? $producto->precio_comparacion_centimos : null;
  $descuento = $precioAntes ? (int) round((1 - $producto->precio_centimos / $precioAntes) * 100) : null;
  $stock = $producto->stockDisponible();
  $especificaciones = $producto->especificaciones ?? [];
  // Puntos clave junto al precio: los del producto o, si no tiene, sus primeras
  // especificaciones (la tabla completa queda más abajo).
  $puntosClave = $producto->features
      ?: array_map(fn ($spec) => "{$spec['clave']}: {$spec['valor']}", array_slice($especificaciones, 0, 5));
@endphp

<section class="section-pad-sm">
  <div class="container">

    <nav class="pd-migas" aria-label="Ruta de navegación">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('productos') }}">Productos</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $producto->nombre }}</li>
      </ol>
    </nav>

    <div class="pd-grid">
      <div class="pd-galeria {{ count($fotos) > 1 ? 'pd-galeria--varias' : '' }}" data-galeria>
        <figure class="pd-foto" data-galeria-foto>
          <img src="{{ $fotos[0]['url'] }}" alt="{{ $producto->nombre }}" width="800" height="800" fetchpriority="high" data-galeria-principal>
          @if (count($fotos) > 1)
            <button type="button" class="pd-flecha pd-flecha--anterior" data-galeria-anterior aria-label="Foto anterior">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <button type="button" class="pd-flecha pd-flecha--siguiente" data-galeria-siguiente aria-label="Foto siguiente">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>
          @endif
        </figure>

        @if (count($fotos) > 1)
          <div class="pd-miniaturas" role="group" aria-label="Fotos del producto">
            @foreach ($fotos as $i => $foto)
              <button type="button"
                      class="pd-miniatura {{ $i === 0 ? 'is-activa' : '' }}"
                      data-galeria-miniatura
                      data-foto="{{ $foto['url'] }}"
                      aria-label="Ver foto {{ $i + 1 }} de {{ count($fotos) }}"
                      @if ($i === 0) aria-current="true" @endif>
                <img src="{{ $foto['thumb_url'] }}" alt="" width="72" height="72" loading="lazy">
              </button>
            @endforeach
          </div>
        @endif
      </div>

      <div class="pd-info" data-oferta-zona>
        @if ($producto->marca || $producto->categoria)
          <p class="pd-marca">{{ $producto->marca?->nombre ?? $producto->categoria->nombre }}</p>
        @endif
        <h1 class="pd-nombre">{{ $producto->nombre }}</h1>

        @if ($puntosClave)
          <ul class="pd-claves">
            @foreach ($puntosClave as $punto)
              <li>{{ $punto }}</li>
            @endforeach
          </ul>
        @endif

        @if ($producto->referencia)
          <p class="pd-ref">Código: {{ $producto->referencia }}</p>
        @endif

        <div class="pd-precio">
          <span class="pd-precio-actual"><span class="moneda">S/</span>{{ number_format($producto->precio_centimos / 100, 2) }}</span>
          @if ($descuento > 0)
            <span class="pd-descuento">-{{ $descuento }}%</span>
          @endif
        </div>
        @if ($precioAntes)
          <p class="pd-precio-regular">Precio regular S/ {{ number_format($precioAntes / 100, 2) }}</p>
        @endif

        @if ($stock <= 0)
          <p class="pd-stock pd-stock--agotado">Agotado</p>
        @elseif ($stock <= $producto->umbral_stock_bajo)
          <p class="pd-stock pd-stock--bajo">{{ $stock === 1 ? '¡Última unidad disponible!' : "¡Últimas {$stock} unidades!" }}</p>
        @elseif ($stock > 10)
          <p class="pd-stock pd-stock--ok">Disponible más de 10 unidades</p>
        @else
          <p class="pd-stock pd-stock--ok">Disponible {{ $stock }} {{ $stock === 1 ? 'unidad' : 'unidades' }}</p>
        @endif

        @if ($stock > 0)
          <span class="pd-cantidad-label" id="pd-cantidad-label">Cantidad</span>
          <div class="pd-compra">
            <div class="pd-stepper" role="group" aria-labelledby="pd-cantidad-label" data-cantidad data-max="{{ min($stock, 99) }}">
              <button type="button" data-cantidad-restar aria-label="Restar una unidad" disabled>−</button>
              <span data-cantidad-valor aria-live="polite">1</span>
              <button type="button" data-cantidad-sumar aria-label="Agregar una unidad" @disabled($stock <= 1)>+</button>
            </div>
            <button type="button"
                    class="btn btn-primary-enlix pd-cta btn-agregar-carrito"
                    data-producto-id="{{ $producto->id }}"
                    data-imagen-url="{{ $producto->imagen_url }}"
                    @if ($producto->precio_comparacion_centimos) data-precio-comparacion-centimos="{{ $producto->precio_comparacion_centimos }}" @endif>
              Agregar al carrito
            </button>
          </div>
          <button type="button" class="btn btn-outline-enlix pd-cta pd-cta--ancho" data-oferta-comprar data-producto-id="{{ $producto->id }}">Comprar ahora</button>
        @else
          <button type="button" class="btn btn-primary-enlix pd-cta pd-cta--ancho" disabled>Agotado</button>
        @endif
        <p class="text-danger mt-2 mb-0" data-oferta-error role="alert" hidden></p>

        <ul class="pd-ventajas">
          @if ($producto->meses_garantia)
            <li>Garantía de {{ $producto->meses_garantia }} {{ $producto->meses_garantia === 1 ? 'mes' : 'meses' }}</li>
          @endif
          <li>Recojo en tienda o envío a domicilio</li>
          <li>Pago seguro con Izipay</li>
        </ul>
        <a href="{{ \App\Support\Contacto::whatsappProducto($producto->nombre) }}" class="pd-whatsapp" target="_blank" rel="noopener">¿Tienes dudas? Escríbenos por WhatsApp</a>
      </div>
    </div>

  </div>
</section>

<section class="pd-detalle section-pad-sm">
  <div class="container">
    <div class="pd-detalle-grid {{ $especificaciones ? 'pd-detalle-grid--doble' : '' }}">

      <div>
        <h2 class="pd-seccion-titulo">Descripción</h2>
        {{-- descripcion ya viene saneada por HTMLPurifier (ver Producto::descripcion()). --}}
        <div class="pd-descripcion">{!! $producto->descripcion !!}</div>
      </div>

      @if ($especificaciones)
        <div>
          <h2 class="pd-seccion-titulo">Especificaciones</h2>
          <dl class="oferta-specs">
            @foreach ($especificaciones as $spec)
              <div class="oferta-spec">
                <dt>{{ $spec['clave'] }}</dt>
                <dd>{{ $spec['valor'] }}</dd>
              </div>
            @endforeach
          </dl>
        </div>
      @endif

    </div>
  </div>
</section>

@if ($relacionados)
  <section class="section-pad-sm">
    <div class="container">
      <h2 class="pd-seccion-titulo mb-4">También te puede interesar</h2>
      <div class="row g-4">
        @foreach ($relacionados as $p)
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
  // Galería: miniaturas, flechas y deslizar con el dedo sobre la foto.
  (function () {
    var galeria = document.querySelector('[data-galeria]');
    var miniaturas = Array.from(galeria.querySelectorAll('[data-galeria-miniatura]'));
    if (miniaturas.length < 2) return;

    var principal = galeria.querySelector('[data-galeria-principal]');
    var foto = galeria.querySelector('[data-galeria-foto]');
    var actual = 0;
    var inicioToque = null;

    function mostrar(indice) {
      actual = (indice + miniaturas.length) % miniaturas.length;
      principal.src = miniaturas[actual].dataset.foto;

      miniaturas.forEach(function (miniatura, i) {
        miniatura.classList.toggle('is-activa', i === actual);
        if (i === actual) {
          miniatura.setAttribute('aria-current', 'true');
        } else {
          miniatura.removeAttribute('aria-current');
        }
      });

      // Mueve solo la tira de miniaturas: scrollIntoView arrastraría la página.
      var activa = miniaturas[actual];
      activa.parentNode.scrollTo({ left: activa.offsetLeft - 8, top: activa.offsetTop - 8 });

      // La siguiente foto ya descargada: el cambio se siente instantáneo.
      new Image().src = miniaturas[(actual + 1) % miniaturas.length].dataset.foto;
    }

    miniaturas.forEach(function (miniatura, i) {
      miniatura.addEventListener('click', function () { mostrar(i); });
    });
    galeria.querySelector('[data-galeria-anterior]').addEventListener('click', function () { mostrar(actual - 1); });
    galeria.querySelector('[data-galeria-siguiente]').addEventListener('click', function () { mostrar(actual + 1); });

    foto.addEventListener('touchstart', function (e) {
      inicioToque = e.changedTouches[0].clientX;
    }, { passive: true });
    foto.addEventListener('touchend', function (e) {
      if (inicioToque === null) return;
      var recorrido = e.changedTouches[0].clientX - inicioToque;
      inicioToque = null;
      if (Math.abs(recorrido) > 40) mostrar(actual + (recorrido < 0 ? 1 : -1));
    }, { passive: true });
  })();

  // Selector de cantidad: lo lee cantidadElegida() en tienda-scripts.
  (function () {
    var selector = document.querySelector('[data-cantidad]');
    if (!selector) return;

    var maximo = parseInt(selector.dataset.max, 10);
    var valor = selector.querySelector('[data-cantidad-valor]');
    var restar = selector.querySelector('[data-cantidad-restar]');
    var sumar = selector.querySelector('[data-cantidad-sumar]');

    function cambiar(diferencia) {
      var cantidad = Math.min(maximo, Math.max(1, parseInt(valor.textContent, 10) + diferencia));
      valor.textContent = cantidad;
      restar.disabled = cantidad <= 1;
      sumar.disabled = cantidad >= maximo;
    }

    restar.addEventListener('click', function () { cambiar(-1); });
    sumar.addEventListener('click', function () { cambiar(1); });
  })();
</script>
@endpush

@endsection
