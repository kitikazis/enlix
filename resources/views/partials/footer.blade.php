@php
  $_grupos_all   = array_values($grupos);
  $_servicios_c1 = array_slice($_grupos_all, 0, 1); // Grupo 1 (mantenimiento)
  $_servicios_c2 = array_slice($_grupos_all, 1);    // Grupos 2-3

  // En una página de servicio, WhatsApp llega con el servicio ya indicado.
  $_wa_url = isset($svc['short'])
      ? \App\Support\Contacto::whatsappServicio($svc['short'])
      : \App\Support\Contacto::whatsapp();

  // La tienda (productos, carrito, checkout) ya tiene su propia barra fija en móvil.
  $_barra_contacto = ($current ?? '') !== 'productos';
@endphp
<footer class="enlix-footer">
  <div class="container">
    <div class="row gy-5">

      {{-- Marca --}}
      <div class="col-12 col-lg-2">
        <img src="{{ asset('assets/img/gemini-svg.svg') }}" alt="Enlix" class="footer-brand-img">
        <p class="footer-tag">
          Mantenimiento preventivo y correctivo de equipos de cómputo e impresoras,
          con servicio presencial y cobertura a nivel nacional.
        </p>
        <p class="footer-info"><span>Dónde estamos</span>Lima, Perú</p>
      </div>

      {{-- Compañía --}}
      <div class="col-12 col-sm-6 col-lg-2">
        <h5>Compañía</h5>
        <ul>
          <li><a href="{{ route('inicio') }}">Inicio</a></li>
          <li><a href="{{ route('nosotros') }}">Nosotros</a></li>
          <li><a href="{{ route('contacto') }}">Contacto</a></li>
        </ul>
      </div>

      {{-- Servicios columna 1 (grupo 1) --}}
      <div class="col-12 col-sm-6 col-lg-2">
        <h5>Servicios</h5>
        <ul class="footer-services">
          @foreach ($_servicios_c1 as $g)
            <li class="footer-services-group">
              <span class="footer-services-group-label">{{ $g['label'] }}</span>
              @foreach ($g['items'] as $sub)
                <a href="{{ route('servicio.show', $sub['slug']) }}">{{ $sub['short'] }}</a>
              @endforeach
            </li>
          @endforeach
        </ul>
      </div>

      {{-- Servicios columna 2 (grupos 2-3) --}}
      <div class="col-12 col-sm-6 col-lg-2 footer-services-col2">
        <ul class="footer-services">
          @foreach ($_servicios_c2 as $g)
            <li class="footer-services-group">
              <span class="footer-services-group-label">{{ $g['label'] }}</span>
              @foreach ($g['items'] as $sub)
                <a href="{{ route('servicio.show', $sub['slug']) }}">{{ $sub['short'] }}</a>
              @endforeach
            </li>
          @endforeach
        </ul>
      </div>

      {{-- Legal --}}
      <div class="col-12 col-sm-6 col-lg-2">
        <h5>Legal</h5>
        <ul>
          <li><a href="{{ route('terminos') }}">Términos y condiciones</a></li>
          <li><a href="{{ route('politica-privacidad') }}">Política de privacidad</a></li>
          <li>
            <a href="{{ route('libro-reclamaciones.index') }}" class="footer-libro-reclamaciones">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
              </svg>
              Libro de Reclamaciones
            </a>
          </li>
        </ul>
      </div>

      {{-- Contacto --}}
      <div class="col-12 col-sm-6 col-lg-2">
        <h5>Contacto</h5>
        <p class="footer-info"><span>Celular</span>963 885 176</p>
        <p class="footer-info"><span>Correo</span><a href="mailto:servicios@enlix.pe" style="color:#fff;">servicios@enlix.pe</a></p>
        <p class="footer-info"><span>LinkedIn</span><a href="https://www.linkedin.com/company/Enlix/" target="_blank" rel="noopener" style="color:#fff;">/company/Enlix</a></p>
      </div>

    </div>

    <div class="footer-bottom">
      <div class="row align-items-center">
        <div class="col-md-6">
          &copy; {{ date('Y') }} Enlix. Todos los derechos reservados.
        </div>
        <div class="col-md-6 text-md-end mt-2 mt-md-0">
          Mantenimiento de equipos de cómputo e impresoras en todo el Perú.
        </div>
      </div>
    </div>
  </div>
</footer>

{{-- ========= WhatsApp flotante ========= --}}
<a class="wa-float {{ $_barra_contacto ? 'wa-float-con-barra' : '' }}"
   href="{{ $_wa_url }}"
   target="_blank" rel="noopener"
   aria-label="Escríbenos por WhatsApp">
  <span class="wa-pulse" aria-hidden="true"></span>
  <span class="wa-label">Escríbenos por WhatsApp</span>
  <svg viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
    <path d="M16.004 4C9.385 4 4 9.385 4 16.004c0 2.114.555 4.18 1.61 6L4 28l6.155-1.59a12 12 0 0 0 5.85 1.498h.005C22.62 27.908 28 22.523 28 15.904 28 9.285 22.623 4 16.004 4zm6.948 17.018c-.295.83-1.715 1.59-2.4 1.688-.612.09-1.387.13-2.243-.14-.515-.165-1.176-.385-2.025-.752-3.566-1.54-5.894-5.131-6.07-5.366-.177-.236-1.46-1.94-1.46-3.706 0-1.766.927-2.633 1.255-2.996.325-.36.706-.45.94-.45h.677c.216 0 .505-.082.792.604.295.706 1.005 2.473 1.094 2.653.09.18.15.39.03.625-.118.236-.176.382-.353.59-.176.205-.372.46-.531.617-.176.176-.36.367-.156.722.205.354.91 1.504 1.957 2.434 1.346 1.2 2.48 1.572 2.832 1.748.354.176.56.146.766-.088.205-.236.882-1.03 1.117-1.385.236-.354.471-.295.796-.176.325.117 2.063.975 2.418 1.152.354.176.59.265.677.412.088.146.088.85-.207 1.682z"/>
  </svg>
</a>

{{-- ========= Barra de contacto fija (solo móvil) ========= --}}
@if ($_barra_contacto)
<div class="contacto-barra-movil" role="region" aria-label="Contacto rápido">
  <a href="tel:{{ \App\Support\Contacto::TELEFONO_TEL }}" class="contacto-barra-btn contacto-barra-llamar">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.35 1.85.59 2.81.72A2 2 0 0 1 22 16.92z"/>
    </svg>
    Llamar
  </a>
  <a href="{{ $_wa_url }}" target="_blank" rel="noopener" class="contacto-barra-btn contacto-barra-whatsapp">
    <svg width="18" height="18" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
      <path d="M16.004 4C9.385 4 4 9.385 4 16.004c0 2.114.555 4.18 1.61 6L4 28l6.155-1.59a12 12 0 0 0 5.85 1.498h.005C22.62 27.908 28 22.523 28 15.904 28 9.285 22.623 4 16.004 4zm6.948 17.018c-.295.83-1.715 1.59-2.4 1.688-.612.09-1.387.13-2.243-.14-.515-.165-1.176-.385-2.025-.752-3.566-1.54-5.894-5.131-6.07-5.366-.177-.236-1.46-1.94-1.46-3.706 0-1.766.927-2.633 1.255-2.996.325-.36.706-.45.94-.45h.677c.216 0 .505-.082.792.604.295.706 1.005 2.473 1.094 2.653.09.18.15.39.03.625-.118.236-.176.382-.353.59-.176.205-.372.46-.531.617-.176.176-.36.367-.156.722.205.354.91 1.504 1.957 2.434 1.346 1.2 2.48 1.572 2.832 1.748.354.176.56.146.766-.088.205-.236.882-1.03 1.117-1.385.236-.354.471-.295.796-.176.325.117 2.063.975 2.418 1.152.354.176.59.265.677.412.088.146.088.85-.207 1.682z"/>
    </svg>
    Cotizar por WhatsApp
  </a>
</div>
@endif
