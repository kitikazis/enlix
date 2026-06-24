@extends('layouts.app')

@section('content')

<!-- Banner -->
<section class="page-banner">
  <img src="https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1400&q=80" alt="" class="banner-img">
  <div class="banner-overlay">
    <div class="container">
      <p class="crumbs">Inicio &nbsp;/&nbsp; Contacto</p>
      <h1>Hablemos</h1>
    </div>
  </div>
</section>

<!-- Intro + datos -->
<section class="section-pad">
  <div class="container">

    <div class="row mb-5 gx-lg-5 gy-4 align-items-center">
      <div class="col-lg-6">
        <p class="eyebrow">Contáctanos</p>
        <h2 style="font-size: clamp(28px, 3.5vw, 40px); line-height: 1.2;">
          Cuéntanos qué necesita tu empresa.
        </h2>
        <p class="text-muted-enlix" style="font-size: 17px;">
          Nuestro equipo te responderá con asesoría, una propuesta clara y los siguientes pasos.
          También puedes escribirnos directamente por correo o llamarnos.
        </p>
        <a href="https://wa.me/51963885176?text=Hola%20Enlix%2C%20me%20gustar%C3%ADa%20obtener%20m%C3%A1s%20informaci%C3%B3n%20sobre%20sus%20servicios."
           target="_blank" rel="noopener"
           class="btn btn-primary-enlix mt-2"
           style="background: #1F8A4A; border-color: #1F8A4A;">
          <svg width="18" height="18" viewBox="0 0 32 32" fill="currentColor" style="vertical-align: -3px; margin-right: 8px;">
            <path d="M16.004 4C9.385 4 4 9.385 4 16.004c0 2.114.555 4.18 1.61 6L4 28l6.155-1.59a12 12 0 0 0 5.85 1.498h.005C22.62 27.908 28 22.523 28 15.904 28 9.285 22.623 4 16.004 4zm6.948 17.018c-.295.83-1.715 1.59-2.4 1.688-.612.09-1.387.13-2.243-.14-.515-.165-1.176-.385-2.025-.752-3.566-1.54-5.894-5.131-6.07-5.366-.177-.236-1.46-1.94-1.46-3.706 0-1.766.927-2.633 1.255-2.996.325-.36.706-.45.94-.45h.677c.216 0 .505-.082.792.604.295.706 1.005 2.473 1.094 2.653.09.18.15.39.03.625-.118.236-.176.382-.353.59-.176.205-.372.46-.531.617-.176.176-.36.367-.156.722.205.354.91 1.504 1.957 2.434 1.346 1.2 2.48 1.572 2.832 1.748.354.176.56.146.766-.088.205-.236.882-1.03 1.117-1.385.236-.354.471-.295.796-.176.325.117 2.063.975 2.418 1.152.354.176.59.265.677.412.088.146.088.85-.207 1.682z"/>
          </svg>
          Escríbenos por WhatsApp
        </a>
      </div>
      <div class="col-lg-6">
        <img src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?auto=format&fit=crop&w=1200&q=80"
             alt="Equipo Enlix atendiendo a un cliente"
             class="img-fluid rounded shadow-soft"
             style="width: 100%; height: 380px; object-fit: cover;">
      </div>
    </div>

    <div class="row g-4 mb-5">
      <div class="col-md-4">
        <div class="contact-card contact-card-phone">
          <div class="ico">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.35 1.85.59 2.81.72A2 2 0 0 1 22 16.92z"/>
            </svg>
          </div>
          <p class="label">Celular</p>
          <p class="value"><a href="tel:+51963885176">963 885 176</a></p>
          <p class="note">Horario laboral: Lunes a viernes, 9:00 a 18:00.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="contact-card contact-card-mail">
          <div class="ico">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </div>
          <p class="label">Correo</p>
          <p class="value"><a href="mailto:servicios@enlix.pe">servicios@enlix.pe</a></p>
          <p class="note">Respondemos en menos de 24 horas hábiles.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="contact-card contact-card-linkedin">
          <div class="ico">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M20.45 20.45h-3.55v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.95v5.66H9.36V9h3.41v1.56h.05a3.74 3.74 0 0 1 3.37-1.85c3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12zM7.12 20.45H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.72v20.56C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.72V1.72C24 .77 23.2 0 22.22 0z"/>
            </svg>
          </div>
          <p class="label">LinkedIn</p>
          <p class="value"><a href="https://www.linkedin.com/company/Enlix/" target="_blank" rel="noopener">/company/Enlix</a></p>
          <p class="note">Síguenos para conocer novedades.</p>
        </div>
      </div>
    </div>

    <!-- Mapa + sede -->
    <div class="row gx-lg-5 gy-5">
      <div class="col-lg-5">
        <p class="eyebrow">Nuestra sede</p>
        <h2 style="font-size: clamp(24px, 2.6vw, 32px); line-height: 1.2;">
          Visítanos en Lima, Perú.
        </h2>
        <p class="text-muted-enlix" style="font-size: 15.5px;">
          Atendemos a empresas en Lima y todo el Perú. Coordina una cita previa con
          nuestro equipo para una asesoría presencial o remota.
        </p>

        <div class="map-info mt-4">
          <div class="map-info-row row-location">
            <div class="ico" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
              </svg>
            </div>
            <div>
              <p class="label">Sede</p>
              <p class="value">Lima, Perú</p>
            </div>
          </div>

          <div class="map-info-row row-hours">
            <div class="ico" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12,6 12,12 16,14"/>
              </svg>
            </div>
            <div>
              <p class="label">Atención al cliente</p>
              <p class="value">Disponibilidad continua, 24/7 los 365 días del año</p>
            </div>
          </div>

          <div class="map-info-row row-work">
            <div class="ico" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="7" width="18" height="13" rx="2"/>
                <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
              </svg>
            </div>
            <div>
              <p class="label">Horario laboral</p>
              <p class="value">Lunes a viernes, 9:00 — 18:00</p>
            </div>
          </div>

          <div class="map-info-row row-whatsapp">
            <div class="ico" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 32 32" fill="currentColor">
                <path d="M16.004 4C9.385 4 4 9.385 4 16.004c0 2.114.555 4.18 1.61 6L4 28l6.155-1.59a12 12 0 0 0 5.85 1.498h.005C22.62 27.908 28 22.523 28 15.904 28 9.285 22.623 4 16.004 4zm6.948 17.018c-.295.83-1.715 1.59-2.4 1.688-.612.09-1.387.13-2.243-.14-.515-.165-1.176-.385-2.025-.752-3.566-1.54-5.894-5.131-6.07-5.366-.177-.236-1.46-1.94-1.46-3.706 0-1.766.927-2.633 1.255-2.996.325-.36.706-.45.94-.45h.677c.216 0 .505-.082.792.604.295.706 1.005 2.473 1.094 2.653.09.18.15.39.03.625-.118.236-.176.382-.353.59-.176.205-.372.46-.531.617-.176.176-.36.367-.156.722.205.354.91 1.504 1.957 2.434 1.346 1.2 2.48 1.572 2.832 1.748.354.176.56.146.766-.088.205-.236.882-1.03 1.117-1.385.236-.354.471-.295.796-.176.325.117 2.063.975 2.418 1.152.354.176.59.265.677.412.088.146.088.85-.207 1.682z"/>
              </svg>
            </div>
            <div>
              <p class="label">WhatsApp</p>
              <p class="value">
                <a href="https://wa.me/51963885176?text=Hola%20Enlix%2C%20me%20gustar%C3%ADa%20obtener%20m%C3%A1s%20informaci%C3%B3n%20sobre%20sus%20servicios."
                   target="_blank" rel="noopener">
                  Escríbenos directamente
                </a>
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="map-wrap">
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d3901.9108492162018!2d-77.03335992415268!3d-12.049654741956138!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMTLCsDAyJzU4LjgiUyA3N8KwMDEnNTAuOCJX!5e0!3m2!1ses-419!2spe!4v1779828310781!5m2!1ses-419!2spe"
            width="600" height="450" style="border:0;"
            allowfullscreen="" loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            title="Ubicación Enlix - Lima, Perú">
          </iframe>
        </div>
      </div>
    </div>

  </div>
</section>

@endsection
