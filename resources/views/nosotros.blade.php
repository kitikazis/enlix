@extends('layouts.app')

@section('content')

<!-- ============ PAGE BANNER ============ -->
<section class="page-banner">
  <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1400&q=80" alt="" class="banner-img">
  <div class="banner-overlay">
    <div class="container">
      <p class="crumbs">Inicio &nbsp;/&nbsp; Nosotros</p>
      <h1>Quiénes somos</h1>
    </div>
  </div>
</section>

<!-- ============ DESCRIPCIÓN ============ -->
<section class="section-pad">
  <div class="container">
    <div class="row gy-5">
      <div class="col-lg-5">
        <p class="eyebrow">Sobre Enlix</p>
        <h2 style="font-size: clamp(28px, 3.5vw, 40px); line-height: 1.2;">
          Una empresa dedicada a la tecnología que mueve a las organizaciones.
        </h2>
      </div>
      <div class="col-lg-7">
        <p style="font-size: 17px; line-height: 1.75;">
          Enlix es una empresa dedicada a brindar soluciones tecnológicas y servicios
          especializados que ayudan a las empresas a mejorar su operación diaria.
        </p>
        <p class="text-muted-enlix" style="font-size: 16px; line-height: 1.75;">
          Nos enfocamos en comprender las necesidades de cada cliente para ofrecer asesoría,
          implementación y soporte en infraestructura tecnológica, equipos y soluciones de TI,
          acompañándolos para que su tecnología funcione de forma confiable y eficiente.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- ============ MISIÓN / VISIÓN / VALORES ============ -->
<section class="section-pad bg-soft" style="border-top: 1px solid var(--enlix-border); border-bottom: 1px solid var(--enlix-border);">
  <div class="container">
    <div class="row mb-5">
      <div class="col-lg-8">
        <p class="eyebrow">Misión, visión y valores</p>
        <h2 style="font-size: clamp(26px, 3vw, 36px); line-height: 1.2;">
          Los principios que guían cada proyecto que entregamos.
        </h2>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-md-6 col-lg-4">
        <div class="mvv-card">
          <h3>Misión</h3>
          <h4>Tecnología que aporta valor real.</h4>
          <p class="text-muted-enlix">
            Brindar soluciones tecnológicas y servicios de TI que ayuden a nuestros clientes
            a trabajar mejor cada día, ofreciendo asesoría, equipos y soporte confiable que
            faciliten sus operaciones y aporten valor real a sus organizaciones.
          </p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="mvv-card">
          <h3>Visión</h3>
          <h4>Impacto positivo en cada empresa.</h4>
          <p class="text-muted-enlix">
            Ser una empresa tecnológica reconocida por ayudar a las organizaciones a crecer
            y operar con confianza, brindando soluciones de TI que simplifiquen su trabajo
            diario y generen un impacto positivo en sus resultados.
          </p>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="mvv-card accent">
          <h3>Valores</h3>
          <ul>
            <li>Compromiso con el cliente</li>
            <li>Honestidad</li>
            <li>Servicio</li>
            <li>Profesionalismo</li>
            <li>Mejora continua</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ APROXIMACIÓN ============ -->
<section class="section-pad">
  <div class="container">
    <div class="row gy-5 align-items-center">
      <div class="col-lg-6">
        <img src="https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1400&q=80" alt="" class="img-fluid rounded">
      </div>
      <div class="col-lg-6">
        <p class="eyebrow">Nuestro enfoque</p>
        <h2 class="mb-4" style="font-size: clamp(26px, 3vw, 36px); line-height: 1.2;">
          Escuchamos primero. Luego proponemos.
        </h2>
        <div class="row gy-4">
          <div class="col-12">
            <div class="d-flex gap-3">
              <div style="flex-shrink: 0; width: 36px; height: 36px; background: var(--enlix-blue-50); color: var(--enlix-blue-800); display: flex; align-items: center; justify-content: center; font-family: var(--font-heading); font-weight: 700; border-radius: 4px;">01</div>
              <div>
                <h4 style="font-size: 17px; margin: 4px 0 6px;">Comprender la operación</h4>
                <p class="text-muted-enlix mb-0" style="font-size: 14.5px;">Analizamos cómo trabaja tu empresa para identificar dónde la tecnología puede aportar.</p>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="d-flex gap-3">
              <div style="flex-shrink: 0; width: 36px; height: 36px; background: var(--enlix-blue-50); color: var(--enlix-blue-800); display: flex; align-items: center; justify-content: center; font-family: var(--font-heading); font-weight: 700; border-radius: 4px;">02</div>
              <div>
                <h4 style="font-size: 17px; margin: 4px 0 6px;">Asesorar e implementar</h4>
                <p class="text-muted-enlix mb-0" style="font-size: 14.5px;">Proponemos soluciones concretas, con equipos, software y servicios apropiados.</p>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="d-flex gap-3">
              <div style="flex-shrink: 0; width: 36px; height: 36px; background: var(--enlix-blue-50); color: var(--enlix-blue-800); display: flex; align-items: center; justify-content: center; font-family: var(--font-heading); font-weight: 700; border-radius: 4px;">03</div>
              <div>
                <h4 style="font-size: 17px; margin: 4px 0 6px;">Acompañar y dar soporte</h4>
                <p class="text-muted-enlix mb-0" style="font-size: 14.5px;">Soporte continuo, mantenimiento y mejoras para que la tecnología funcione siempre.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ CTA ============ -->
<section class="cta-strip">
  <div class="container position-relative">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <h2>Conversemos sobre lo que tu empresa necesita.</h2>
        <p>Un equipo cercano, propuestas claras y soporte real cuando lo necesitas.</p>
      </div>
      <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
        <a href="{{ route('contacto') }}" class="btn btn-primary-enlix">Contáctanos</a>
      </div>
    </div>
  </div>
</section>

@endsection
