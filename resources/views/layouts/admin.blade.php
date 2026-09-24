<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $titulo ?? 'Admin - Enlix' }}</title>
  <link rel="shortcut icon" href="{{ asset('assets/img/favicon.png') }}" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .admin-sidebar { width: 230px; background: #0b2447; }
    .admin-sidebar .nav-link { color: rgba(255,255,255,.75); }
    .admin-sidebar .nav-link.active { color: #fff; background: rgba(255,255,255,.12); font-weight: 600; }
    .admin-sidebar .nav-link:hover { color: #fff; }
  </style>
</head>
<body style="background:#f5f6f8;">

@auth
  @php
    $enlacesMenu = [
      ['ruta' => 'admin.dashboard', 'url' => route('admin.dashboard'), 'activo' => request()->routeIs('admin.dashboard'), 'label' => 'Dashboard'],
      ['ruta' => 'admin.productos.index', 'url' => route('admin.productos.index'), 'activo' => request()->routeIs('admin.productos.*'), 'label' => 'Productos'],
    ];
  @endphp

  <div class="d-flex">
    <nav class="admin-sidebar d-none d-md-flex flex-column p-3 flex-shrink-0" style="min-height: 100vh;">
      <span class="navbar-brand text-white mb-4">Enlix · Admin</span>
      <div class="nav nav-pills flex-column mb-auto gap-1">
        @foreach ($enlacesMenu as $enlace)
          <a class="nav-link {{ $enlace['activo'] ? 'active' : '' }}" href="{{ $enlace['url'] }}">{{ $enlace['label'] }}</a>
        @endforeach
      </div>
      <form method="POST" action="{{ route('admin.logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="btn btn-outline-light btn-sm w-100">Cerrar sesión</button>
      </form>
    </nav>

    <div class="offcanvas offcanvas-start admin-sidebar text-bg-dark d-md-none" tabindex="-1" id="sidebarMovil">
      <div class="offcanvas-header">
        <span class="navbar-brand text-white mb-0">Enlix · Admin</span>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
      </div>
      <div class="offcanvas-body d-flex flex-column">
        <div class="nav nav-pills flex-column mb-auto gap-1">
          @foreach ($enlacesMenu as $enlace)
            <a class="nav-link {{ $enlace['activo'] ? 'active' : '' }}" href="{{ $enlace['url'] }}">{{ $enlace['label'] }}</a>
          @endforeach
        </div>
        <form method="POST" action="{{ route('admin.logout') }}" class="mt-4">
          @csrf
          <button type="submit" class="btn btn-outline-light btn-sm w-100">Cerrar sesión</button>
        </form>
      </div>
    </div>

    <div class="flex-grow-1" style="min-width: 0;">
      <nav class="navbar navbar-dark d-md-none" style="background:#0b2447;">
        <div class="container-fluid px-3">
          <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMovil" aria-label="Abrir menú">
            ☰
          </button>
          <span class="navbar-brand mb-0">Enlix · Admin</span>
          <span style="width: 2.5rem;"></span>
        </div>
      </nav>

      <main class="container-fluid px-3 px-lg-4 py-4">
        @yield('content')
      </main>
    </div>
  </div>
@else
  <main class="container-fluid px-3 px-lg-4 py-4">
    @yield('content')
  </main>
@endauth

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
