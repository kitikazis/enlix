@extends('layouts.app')

@section('content')

@php
  $valores = [
    ['Excelencia', 'Trabajamos con dedicación, precisión y atención al detalle para brindar servicios de alta calidad que generen resultados confiables y satisfactorios para nuestros clientes.'],
    ['Compromiso', 'Asumimos cada servicio con responsabilidad y dedicación, procurando cumplir nuestros acuerdos y ofrecer soluciones que contribuyan al buen funcionamiento de las operaciones de nuestros clientes.'],
    ['Honestidad y transparencia', 'Actuamos con integridad y claridad en cada interacción, brindando información precisa, recomendaciones sinceras y condiciones comerciales transparentes.'],
    ['Confianza', 'Construimos relaciones sólidas y duraderas mediante un servicio consistente, una comunicación cercana y el cumplimiento de nuestros compromisos.'],
    ['Orientación al cliente', 'Escuchamos y comprendemos las necesidades de cada empresa para ofrecer una atención personalizada y soluciones tecnológicas acordes con sus objetivos y posibilidades.'],
    ['Profesionalismo', 'Aplicamos nuestros conocimientos técnicos, experiencia y buenas prácticas para brindar una atención organizada, eficiente y orientada a resultados.'],
    ['Innovación y mejora continua', 'Promovemos el aprendizaje, la actualización tecnológica y la búsqueda constante de mejores soluciones para optimizar nuestros servicios y aportar mayor valor a nuestros clientes.'],
    ['Responsabilidad', 'Cuidamos los equipos, la información y los recursos que nuestros clientes ponen bajo nuestra responsabilidad, actuando con criterio profesional y respeto por sus operaciones.'],
    ['Cercanía y empatía', 'Ofrecemos un trato amable, humano y personalizado, comprendiendo las necesidades de cada cliente y acompañándolo con disposición para encontrar la mejor solución.'],
    ['Calidad garantizada', 'Nos esforzamos por entregar servicios que cumplan con los estándares técnicos acordados, respaldando nuestro trabajo con garantías claras y atención posterior al servicio.'],
  ];
@endphp

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
          Un equipo peruano apasionado por la tecnología al servicio de las empresas.
        </h2>
      </div>
      <div class="col-lg-7">
        <p style="font-size: 17px; line-height: 1.75;">
          En Enlix nos dedicamos a cuidar la tecnología que hace funcionar a las organizaciones.
          Nos especializamos en el mantenimiento preventivo y correctivo de laptops, PCs,
          All-in-One, equipos Mac e impresoras de todas las marcas, con un servicio
          completamente presencial (on-site y en nuestro local) y cobertura a nivel nacional.
        </p>
        <p class="text-muted-enlix" style="font-size: 16px; line-height: 1.75;">
          Nuestro trabajo se caracteriza por ser excelente y garantizado. Cada intervención la
          realizamos con rigor técnico, atención al detalle y el compromiso de entregar resultados
          confiables que permitan a las empresas operar sin interrupciones.
        </p>
        <p class="text-muted-enlix" style="font-size: 16px; line-height: 1.75;">
          Además del mantenimiento y la reparación, acompañamos a nuestros clientes con la venta de
          equipos informáticos, accesorios y suministros de impresión, instalación y configuración de
          sistemas, migración de estaciones de trabajo, control de activos TI y asesoría para la
          renovación tecnológica.
        </p>
        <p class="text-muted-enlix mb-0" style="font-size: 16px; line-height: 1.75;">
          Creemos en las relaciones de largo plazo. Por eso trabajamos con transparencia,
          profesionalismo y un enfoque práctico que genera confianza y resultados concretos.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- ============ MISIÓN / VISIÓN ============ -->
<section class="section-pad bg-soft" style="border-top: 1px solid var(--enlix-border); border-bottom: 1px solid var(--enlix-border);">
  <div class="container">
    <div class="row mb-5">
      <div class="col-lg-8">
        <p class="eyebrow">Misión y visión</p>
        <h2 style="font-size: clamp(26px, 3vw, 36px); line-height: 1.2;">
          Hacia dónde vamos y cómo lo hacemos.
        </h2>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-md-6">
        <div class="mvv-card">
          <h3>Misión</h3>
          <h4>Soluciones integrales con calidad garantizada.</h4>
          <p class="text-muted-enlix mb-0">
            Brindar soluciones tecnológicas integrales mediante servicios de mantenimiento preventivo
            y correctivo de equipos de cómputo e impresión, ofreciendo atención presencial, asesoría
            especializada y resultados de calidad garantizados. En Enlix trabajamos con profesionalismo,
            transparencia y compromiso para optimizar el rendimiento de los equipos, prolongar su vida
            útil y contribuir a la continuidad operativa de las empresas del Perú, construyendo
            relaciones de confianza a largo plazo.
          </p>
        </div>
      </div>
      <div class="col-md-6">
        <div class="mvv-card accent">
          <h3>Visión</h3>
          <h4>El aliado tecnológico de confianza de las empresas del Perú.</h4>
          <p class="text-muted-enlix mb-0">
            Convertirnos en el aliado tecnológico de confianza de las empresas del Perú, asegurando que
            su infraestructura de cómputo e impresión funcione siempre de manera óptima, mediante un
            servicio presencial de excelencia y resultados garantizados.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ VALORES ============ -->
<section class="section-pad">
  <div class="container">
    <div class="row mb-5">
      <div class="col-lg-8">
        <p class="eyebrow">Nuestros valores</p>
        <h2 style="font-size: clamp(26px, 3vw, 36px); line-height: 1.2;">
          Los principios que respaldan cada servicio que entregamos.
        </h2>
      </div>
    </div>

    <ol class="valores-grid">
      @foreach ($valores as $i => [$nombre, $descripcion])
        <li class="valor-item">
          <span class="valor-num" aria-hidden="true">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
          <div class="valor-body">
            <h3>{{ $nombre }}</h3>
            <p>{{ $descripcion }}</p>
          </div>
        </li>
      @endforeach
    </ol>
  </div>
</section>

<!-- ============ APROXIMACIÓN ============ -->
<section class="section-pad bg-soft" style="border-top: 1px solid var(--enlix-border);">
  <div class="container">
    <div class="row gy-5 align-items-center">
      <div class="col-lg-6">
        <img src="https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?auto=format&fit=crop&w=1400&q=80" alt="Técnico de Enlix realizando mantenimiento a un equipo" class="img-fluid rounded">
      </div>
      <div class="col-lg-6">
        <p class="eyebrow">Cómo trabajamos</p>
        <h2 class="mb-4" style="font-size: clamp(26px, 3vw, 36px); line-height: 1.2;">
          Diagnosticamos, resolvemos y respaldamos.
        </h2>
        <div class="row gy-4">
          <div class="col-12">
            <div class="d-flex gap-3">
              <div style="flex-shrink: 0; width: 36px; height: 36px; background: var(--enlix-blue-50); color: var(--enlix-blue-800); display: flex; align-items: center; justify-content: center; font-family: var(--font-heading); font-weight: 700; border-radius: 4px;">01</div>
              <div>
                <h4 style="font-size: 17px; margin: 4px 0 6px;">Diagnóstico claro</h4>
                <p class="text-muted-enlix mb-0" style="font-size: 14.5px;">Evaluamos tus equipos y te explicamos la falla y la solución con información precisa y recomendaciones sinceras.</p>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="d-flex gap-3">
              <div style="flex-shrink: 0; width: 36px; height: 36px; background: var(--enlix-blue-50); color: var(--enlix-blue-800); display: flex; align-items: center; justify-content: center; font-family: var(--font-heading); font-weight: 700; border-radius: 4px;">02</div>
              <div>
                <h4 style="font-size: 17px; margin: 4px 0 6px;">Intervención presencial</h4>
                <p class="text-muted-enlix mb-0" style="font-size: 14.5px;">Trabajamos on-site en tus oficinas o en nuestro local, en cualquier parte del Perú, con rigor técnico y atención al detalle.</p>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="d-flex gap-3">
              <div style="flex-shrink: 0; width: 36px; height: 36px; background: var(--enlix-blue-50); color: var(--enlix-blue-800); display: flex; align-items: center; justify-content: center; font-family: var(--font-heading); font-weight: 700; border-radius: 4px;">03</div>
              <div>
                <h4 style="font-size: 17px; margin: 4px 0 6px;">Garantía y seguimiento</h4>
                <p class="text-muted-enlix mb-0" style="font-size: 14.5px;">Respaldamos nuestro trabajo con garantías claras y atención posterior al servicio.</p>
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
        <h2>Tu tecnología, en las mejores manos.</h2>
        <p>Un equipo cercano, propuestas claras y un trabajo garantizado cuando lo necesitas.</p>
      </div>
      <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
        <a href="{{ route('contacto') }}" class="btn btn-primary-enlix">Contáctanos</a>
      </div>
    </div>
  </div>
</section>

@endsection
