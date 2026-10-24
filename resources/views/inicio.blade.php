@extends('layouts.app')

@section('content')

<!-- ============ HERO ============ -->
<section class="hero">
  <div class="container position-relative">
    <div class="row">
      <div class="col-lg-10 col-xl-9">
        <div class="hero-line"></div>
        <p class="hero-kicker">Enlix · Servicio técnico para empresas</p>
        <h1>Mantenimiento de computadoras e impresoras para empresas, en todo el Perú.</h1>
        <p class="lead">
          Laptops, PCs, All-in-One, Mac e impresoras de todas las marcas.
          Servicio 100&nbsp;% presencial, on-site o en nuestro local, con trabajo garantizado.
        </p>
        <div class="hero-cta">
          <a href="{{ \App\Support\Contacto::whatsapp('Hola Enlix, quiero cotizar un servicio para mis equipos.') }}"
             target="_blank" rel="noopener" class="btn btn-whatsapp-enlix">
            <svg width="18" height="18" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
              <path d="M16.004 4C9.385 4 4 9.385 4 16.004c0 2.114.555 4.18 1.61 6L4 28l6.155-1.59a12 12 0 0 0 5.85 1.498h.005C22.62 27.908 28 22.523 28 15.904 28 9.285 22.623 4 16.004 4zm6.948 17.018c-.295.83-1.715 1.59-2.4 1.688-.612.09-1.387.13-2.243-.14-.515-.165-1.176-.385-2.025-.752-3.566-1.54-5.894-5.131-6.07-5.366-.177-.236-1.46-1.94-1.46-3.706 0-1.766.927-2.633 1.255-2.996.325-.36.706-.45.94-.45h.677c.216 0 .505-.082.792.604.295.706 1.005 2.473 1.094 2.653.09.18.15.39.03.625-.118.236-.176.382-.353.59-.176.205-.372.46-.531.617-.176.176-.36.367-.156.722.205.354.91 1.504 1.957 2.434 1.346 1.2 2.48 1.572 2.832 1.748.354.176.56.146.766-.088.205-.236.882-1.03 1.117-1.385.236-.354.471-.295.796-.176.325.117 2.063.975 2.418 1.152.354.176.59.265.677.412.088.146.088.85-.207 1.682z"/>
            </svg>
            Cotizar por WhatsApp
          </a>
          <a href="#servicios" class="btn btn-outline-enlix">Ver servicios</a>
        </div>
      </div>
    </div>

    <div class="hero-stats">
      <div class="row gy-4">
        <div class="col-6 col-md-3">
          <span class="stat-num">{{ count(\App\Support\Catalogo::items()) }}</span>
          <span class="stat-lbl">Servicios especializados</span>
        </div>
        <div class="col-6 col-md-3">
          <span class="stat-num">100%</span>
          <span class="stat-lbl">Atención presencial</span>
        </div>
        <div class="col-6 col-md-3">
          <span class="stat-num">Perú</span>
          <span class="stat-lbl">Cobertura nacional</span>
        </div>
        <div class="col-6 col-md-3">
          <span class="stat-num">Garantía</span>
          <span class="stat-lbl">En cada servicio</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ LOGOS / STRIP ============ -->
<section class="logos-strip">
  <div class="container">
    <p class="label text-center mb-4">Atendemos equipos de todas las marcas</p>
    <div class="row align-items-center justify-content-around gy-4">
      <div class="col-6 col-md-2 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 132 28" class="brand-logo-img" role="img" aria-label="Microsoft">
          <rect width="12" height="12" fill="#F25022"/>
          <rect x="14" width="12" height="12" fill="#7FBA00"/>
          <rect y="14" width="12" height="12" fill="#00A4EF"/>
          <rect x="14" y="14" width="12" height="12" fill="#FFB900"/>
          <text x="32" y="21" font-family="'Segoe UI','Inter',Arial,sans-serif" font-size="19" font-weight="600" fill="#737373" letter-spacing="-0.3">Microsoft</text>
        </svg>
      </div>
      <div class="col-6 col-md-2 text-center">
        <img src="https://cdn.simpleicons.org/hp/0096D6" alt="HP" class="brand-logo-img">
      </div>
      <div class="col-6 col-md-2 text-center">
        <img src="https://cdn.simpleicons.org/lenovo/E2231A" alt="Lenovo" class="brand-logo-img">
      </div>
      <div class="col-6 col-md-2 text-center">
        <img src="https://cdn.simpleicons.org/dell/007DB8" alt="Dell" class="brand-logo-img">
      </div>
      <div class="col-6 col-md-2 text-center">
        <img src="https://cdn.simpleicons.org/apple/555555" alt="Apple" class="brand-logo-img">
      </div>
      <div class="col-6 col-md-2 text-center">
        <img src="https://cdn.simpleicons.org/epson/003399" alt="Epson" class="brand-logo-img">
      </div>
    </div>
  </div>
</section>

<!-- ============ INTRO / VALOR ============ -->
<section class="section-pad">
  <div class="container">
    <div class="row gy-5 align-items-center">
      <div class="col-lg-5">
        <p class="eyebrow">Quiénes somos</p>
        <h2 class="mb-3" style="font-size: clamp(28px, 3.5vw, 40px); line-height: 1.2;">
          Equipos que funcionan, una operación que no se detiene.
        </h2>
        <p style="font-size: 16px;">
          En Enlix somos una empresa peruana especializada en el mantenimiento preventivo
          y correctivo de equipos de cómputo e impresoras. Nuestro compromiso es realizar
          un trabajo excelente y garantizado, cuidando cada detalle para que tus equipos
          funcionen de manera confiable, eficiente y segura.
        </p>
        <p class="text-muted-enlix" style="font-size: 16px;">
          Combinamos experiencia técnica, atención personalizada y soluciones prácticas
          que ayudan a las empresas a mantener su operación sin interrupciones.
        </p>
        <a href="{{ route('nosotros') }}" class="btn btn-ghost-enlix mt-2">
          Conoce más sobre Enlix <span class="arrow">&rarr;</span>
        </a>
      </div>

      <div class="col-lg-7">
        <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1400&q=80"
             alt="Equipo Enlix trabajando" class="img-fluid rounded shadow-soft">
      </div>
    </div>
  </div>
</section>

<!-- ============ SERVICIOS ============ -->
<section id="servicios" class="section-pad bg-soft" style="border-top: 1px solid var(--enlix-border); border-bottom: 1px solid var(--enlix-border);">
  <div class="container">
    <div class="row mb-5 align-items-end">
      <div class="col-lg-7">
        <p class="eyebrow">Nuestros servicios</p>
        <h2 style="font-size: clamp(28px, 3.5vw, 40px); line-height: 1.2;">
          Del mantenimiento a la renovación de tus equipos, en un solo lugar.
        </h2>
      </div>
      <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
        <p class="text-muted-enlix mb-0" style="font-size: 15px;">
          Contrata un servicio puntual o una solución integral para todo tu parque informático.
        </p>
      </div>
    </div>

    @foreach ($grupos as $g)
      <div class="home-svc-group">
        <div class="home-svc-group-head">
          <span class="home-svc-group-num">{{ $g['num'] }}</span>
          <h3>{{ $g['label'] }}</h3>
        </div>
        @php
          // Columnas según cantidad, para que ningún grupo deje tarjetas huérfanas.
          $colClase = match (count($g['items'])) {
              1       => 'col-12',
              4       => 'col-md-6 col-lg-3',
              default => 'col-md-6 col-lg-4',
          };
        @endphp
        <div class="row g-4">
          @foreach ($g['items'] as $sub)
            @php($svc = \App\Support\Catalogo::find($sub['slug']))
            <div class="{{ $colClase }}">
              <a href="{{ route('servicio.show', $sub['slug']) }}" class="home-svc-card">
                <span class="home-svc-card-tag">{{ $svc['badge'] }}</span>
                <h4>{{ $svc['short'] }}</h4>
                <p>{{ $svc['lead'] }}</p>
                <span class="home-svc-card-cta">Ver servicio <span class="arrow">&rarr;</span></span>
              </a>
            </div>
          @endforeach
        </div>
      </div>
    @endforeach

    <!-- CTA A medida -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="svc-custom-cta">
          <div>
            <p class="num">A tu medida</p>
            <h3>¿Necesitas algo distinto?</h3>
            <p>Cuéntanos qué necesitan tus equipos y diseñamos una solución específica para tu empresa.</p>
          </div>
          <a href="{{ route('contacto') }}" class="btn btn-primary-enlix" style="background: var(--enlix-accent); border-color: var(--enlix-accent); color: var(--enlix-blue-900);">Conversemos</a>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- ============ POR QUÉ ELEGIRNOS ============ -->
<section class="section-pad">
  <div class="container">
    <div class="row gy-5">
      <div class="col-lg-4">
        <p class="eyebrow">Por qué Enlix</p>
        <h2 style="font-size: clamp(26px, 3vw, 36px); line-height: 1.2;">
          No solo solucionamos problemas: construimos confianza.
        </h2>
      </div>
      <div class="col-lg-8">
        <div class="row g-4">
          <div class="col-md-6">
            <div class="p-4" style="border-left: 3px solid var(--enlix-blue-700);">
              <h4 style="font-size: 17px; margin: 0 0 10px;">Trabajo excelente y garantizado</h4>
              <p class="text-muted-enlix mb-0" style="font-size: 14.5px;">
                Cada intervención se realiza con rigor técnico y atención al detalle, y la respaldamos con garantías claras.
              </p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-4" style="border-left: 3px solid var(--enlix-blue-700);">
              <h4 style="font-size: 17px; margin: 0 0 10px;">100&nbsp;% presencial, en todo el Perú</h4>
              <p class="text-muted-enlix mb-0" style="font-size: 14.5px;">
                Atendemos on-site en tus oficinas o en nuestro local, con cobertura a nivel nacional.
              </p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-4" style="border-left: 3px solid var(--enlix-blue-700);">
              <h4 style="font-size: 17px; margin: 0 0 10px;">Todas las marcas y equipos</h4>
              <p class="text-muted-enlix mb-0" style="font-size: 14.5px;">
                Laptops, PCs, All-in-One, Mac e impresoras de cualquier marca, con un mismo estándar de servicio.
              </p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-4" style="border-left: 3px solid var(--enlix-blue-700);">
              <h4 style="font-size: 17px; margin: 0 0 10px;">Relaciones de largo plazo</h4>
              <p class="text-muted-enlix mb-0" style="font-size: 14.5px;">
                Te acompañamos con profesionalismo, transparencia y resultados que se notan, antes y después de cada servicio.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ CTA STRIP ============ -->
<section class="cta-strip">
  <div class="container position-relative">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <h2>¿Tus equipos necesitan mantenimiento o reparación?</h2>
        <p>Coordinamos una visita on-site o recibimos tus equipos en nuestro local. Te respondemos con una propuesta clara.</p>
      </div>
      <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
        <a href="{{ \App\Support\Contacto::whatsapp('Hola Enlix, quiero cotizar un servicio para mis equipos.') }}" target="_blank" rel="noopener" class="btn btn-whatsapp-enlix">Cotizar por WhatsApp</a>
      </div>
    </div>
  </div>
</section>

@endsection
