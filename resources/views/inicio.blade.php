@extends('layouts.app')

@section('content')

<!-- ============ HERO ============ -->
<section class="hero">
  <div class="container position-relative">
    <div class="row">
      <div class="col-lg-9">
        <div class="hero-line"></div>
        <h1>ENLIX</h1>
        <p class="lead">
          ¿Buscas soluciones tecnológicas? Estamos para ayudarte.
          Asesoría, implementación y soporte en infraestructura tecnológica,
          equipos y soluciones de TI para empresas que necesitan operar
          con confianza y eficiencia.
        </p>
        <div class="hero-cta">
          <a href="#servicios" class="btn btn-primary-enlix">Ver nuestros servicios</a>
          <a href="{{ route('contacto') }}" class="btn btn-outline-enlix">Contáctanos</a>
        </div>
      </div>
    </div>

    <div class="hero-stats">
      <div class="row gy-4">
        <div class="col-6 col-md-3">
          <span class="stat-num">15+</span>
          <span class="stat-lbl">Servicios especializados</span>
        </div>
        <div class="col-6 col-md-3">
          <span class="stat-num">5</span>
          <span class="stat-lbl">Áreas de práctica</span>
        </div>
        <div class="col-6 col-md-3">
          <span class="stat-num">B2B</span>
          <span class="stat-lbl">Enfoque corporativo</span>
        </div>
        <div class="col-6 col-md-3">
          <span class="stat-num">2026</span>
          <span class="stat-lbl">Operaciones activas</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ LOGOS / STRIP ============ -->
<section class="logos-strip">
  <div class="container">
    <p class="label text-center mb-4">Trabajamos con marcas reconocidas del ecosistema TI</p>
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
        <img src="https://cdn.simpleicons.org/cisco/1BA0D7" alt="Cisco" class="brand-logo-img">
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
          Tecnología que sostiene tu operación día a día.
        </h2>
        <p class="text-muted-enlix" style="font-size: 16px;">
          En Enlix nos enfocamos en comprender las necesidades de cada cliente para ofrecer
          asesoría, implementación y soporte en infraestructura tecnológica, equipos y soluciones
          de TI. Acompañamos a las empresas para que su tecnología funcione de forma confiable y eficiente.
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
          Quince servicios independientes para tu operación tecnológica.
        </h2>
      </div>
      <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
        <p class="text-muted-enlix mb-0" style="font-size: 15px;">
          Cada servicio se contrata por separado o como parte de una solución integral.
        </p>
      </div>
    </div>

    @foreach ($grupos as $g)
      <div class="home-svc-group">
        <div class="home-svc-group-head">
          <span class="home-svc-group-num">{{ $g['num'] }}</span>
          <h3>{{ $g['label'] }}</h3>
        </div>
        <div class="row g-4">
          @foreach ($g['items'] as $sub)
            @php($svc = \App\Support\Catalogo::find($sub['slug']))
            <div class="col-md-6 col-lg-4">
              <a href="{{ route('servicio.show', $sub['slug']) }}" class="home-svc-card">
                <span class="home-svc-card-tag">{{ $g['label'] }}</span>
                <h4>{{ $svc['short'] }}</h4>
                <p>{{ mb_substr($svc['lead'], 0, 110) }}…</p>
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
            <p>Cuéntanos sobre tu proyecto y diseñamos una solución específica para tu organización.</p>
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
          Una relación de largo plazo, no una transacción.
        </h2>
      </div>
      <div class="col-lg-8">
        <div class="row g-4">
          <div class="col-md-6">
            <div class="p-4" style="border-left: 3px solid var(--enlix-blue-700);">
              <h4 style="font-size: 17px; margin: 0 0 10px;">Asesoría especializada</h4>
              <p class="text-muted-enlix mb-0" style="font-size: 14.5px;">
                Comprendemos tu operación antes de proponer una solución. Recomendamos lo que tu empresa realmente necesita.
              </p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-4" style="border-left: 3px solid var(--enlix-blue-700);">
              <h4 style="font-size: 17px; margin: 0 0 10px;">Continuidad operativa</h4>
              <p class="text-muted-enlix mb-0" style="font-size: 14.5px;">
                Soporte continuo, suministros disponibles y mantenimiento preventivo para que tu tecnología no detenga el negocio.
              </p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-4" style="border-left: 3px solid var(--enlix-blue-700);">
              <h4 style="font-size: 17px; margin: 0 0 10px;">Calidad y garantía</h4>
              <p class="text-muted-enlix mb-0" style="font-size: 14.5px;">
                Trabajamos con equipos y suministros de marcas reconocidas, con garantía y respaldo formal.
              </p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-4" style="border-left: 3px solid var(--enlix-blue-700);">
              <h4 style="font-size: 17px; margin: 0 0 10px;">Acompañamiento integral</h4>
              <p class="text-muted-enlix mb-0" style="font-size: 14.5px;">
                Desde la asesoría inicial hasta el soporte posterior, acompañamos cada etapa del proyecto.
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
        <h2>¿Hablamos sobre tu próximo proyecto tecnológico?</h2>
        <p>Cuéntanos qué necesita tu empresa. Te respondemos con una propuesta clara.</p>
      </div>
      <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
        <a href="{{ route('contacto') }}" class="btn btn-primary-enlix">Solicitar cotización</a>
      </div>
    </div>
  </div>
</section>

@endsection
