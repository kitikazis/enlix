@php($current = $current ?? '')
<nav class="navbar navbar-expand-lg enlix-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand" href="{{ route('inicio') }}" aria-label="Enlix - Inicio">
      <img src="{{ asset('assets/img/gemini-svg.svg') }}" alt="Enlix" class="brand-logo">
    </a>

    <button class="navbar-toggler border-0" type="button"
            data-bs-toggle="collapse" data-bs-target="#enlixNav"
            aria-controls="enlixNav" aria-expanded="false" aria-label="Abrir menú">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="enlixNav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item">
          <a class="nav-link {{ $current === 'inicio' ? 'active' : '' }}" href="{{ route('inicio') }}">Inicio</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $current === 'nosotros' ? 'active' : '' }}" href="{{ route('nosotros') }}">Nosotros</a>
        </li>
        <li class="nav-item dropdown enlix-flyout-menu">
          <a class="nav-link dropdown-toggle {{ $current === 'servicios' ? 'active' : '' }}"
             href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Servicios
          </a>
          <ul class="dropdown-menu flyout-parent">
            @foreach ($grupos as $g)
              <li class="flyout-item">
                <a href="{{ route('servicio.show', $g['items'][0]['slug']) }}" class="flyout-link">
                  <span class="flyout-link-text">
                    <span class="flyout-num">{{ $g['num'] }}</span>
                    {{ $g['label'] }}
                  </span>
                  <span class="flyout-chev" aria-hidden="true">&rsaquo;</span>
                </a>
                <ul class="flyout-sub">
                  <li class="flyout-sub-title">{{ $g['label'] }}</li>
                  @foreach ($g['items'] as $sub)
                    <li><a href="{{ route('servicio.show', $sub['slug']) }}">{{ $sub['short'] }}</a></li>
                  @endforeach
                </ul>
              </li>
            @endforeach
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $current === 'productos' ? 'active' : '' }}" href="{{ route('productos') }}">Productos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $current === 'contacto' ? 'active' : '' }}" href="{{ route('contacto') }}">Contacto</a>
        </li>
        <li class="nav-item mt-3 mt-lg-0">
          <a class="nav-link enlix-carrito-link" href="{{ route('carrito.index') }}" aria-label="Ver carrito ({{ $carritoCantidad ?? 0 }} productos)">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="9" cy="21" r="1"></circle>
              <circle cx="20" cy="21" r="1"></circle>
              <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            <span class="enlix-carrito-badge {{ ($carritoCantidad ?? 0) < 1 ? 'is-empty' : '' }}" id="carrito-badge" data-carrito-badge>{{ $carritoCantidad ?? 0 }}</span>
          </a>
        </li>
        <li class="nav-item ms-lg-3 mt-3 mt-lg-0">
          <a class="btn btn-primary-enlix" href="{{ route('contacto') }}">Solicitar cotización</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
