@php
  $_grupos_all   = array_values($grupos);
  $_servicios_c1 = array_slice($_grupos_all, 0, 3); // Grupos 1-3
  $_servicios_c2 = array_slice($_grupos_all, 3);    // Grupos 4-5
@endphp
<footer class="enlix-footer">
  <div class="container">
    <div class="row gy-5">

      {{-- Marca --}}
      <div class="col-12 col-lg-3">
        <img src="{{ asset('assets/img/enlix-logo.jpg') }}" alt="Enlix" class="footer-brand-img">
        <p class="footer-tag">
          Soluciones tecnológicas y servicios de TI que ayudan a las empresas a operar
          con confianza, eficiencia y continuidad.
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

      {{-- Servicios columna 1 (grupos 1-3) --}}
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

      {{-- Servicios columna 2 (grupos 4-5) --}}
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

      {{-- Contacto --}}
      <div class="col-12 col-sm-6 col-lg-3">
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
          Soluciones tecnológicas para empresas en el Perú.
        </div>
      </div>
    </div>
  </div>
</footer>

{{-- ========= WhatsApp flotante ========= --}}
<a class="wa-float"
   href="https://wa.me/51963885176?text=Hola%20Enlix%2C%20me%20gustar%C3%ADa%20obtener%20m%C3%A1s%20informaci%C3%B3n%20sobre%20sus%20servicios."
   target="_blank" rel="noopener"
   aria-label="Escríbenos por WhatsApp">
  <span class="wa-pulse" aria-hidden="true"></span>
  <span class="wa-label">Escríbenos por WhatsApp</span>
  <svg viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
    <path d="M16.004 4C9.385 4 4 9.385 4 16.004c0 2.114.555 4.18 1.61 6L4 28l6.155-1.59a12 12 0 0 0 5.85 1.498h.005C22.62 27.908 28 22.523 28 15.904 28 9.285 22.623 4 16.004 4zm6.948 17.018c-.295.83-1.715 1.59-2.4 1.688-.612.09-1.387.13-2.243-.14-.515-.165-1.176-.385-2.025-.752-3.566-1.54-5.894-5.131-6.07-5.366-.177-.236-1.46-1.94-1.46-3.706 0-1.766.927-2.633 1.255-2.996.325-.36.706-.45.94-.45h.677c.216 0 .505-.082.792.604.295.706 1.005 2.473 1.094 2.653.09.18.15.39.03.625-.118.236-.176.382-.353.59-.176.205-.372.46-.531.617-.176.176-.36.367-.156.722.205.354.91 1.504 1.957 2.434 1.346 1.2 2.48 1.572 2.832 1.748.354.176.56.146.766-.088.205-.236.882-1.03 1.117-1.385.236-.354.471-.295.796-.176.325.117 2.063.975 2.418 1.152.354.176.59.265.677.412.088.146.088.85-.207 1.682z"/>
  </svg>
</a>
