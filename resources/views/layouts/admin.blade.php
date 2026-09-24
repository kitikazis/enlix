<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $titulo ?? 'Admin - Enlix' }}</title>
  <link rel="shortcut icon" href="{{ asset('assets/img/favicon.png') }}" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#f5f6f8;">

@auth
<nav class="navbar navbar-dark navbar-expand-md" style="background:#0b2447;">
  <div class="container-fluid px-3 px-lg-4">
    <span class="navbar-brand mb-0 h1">Enlix · Admin</span>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navAdmin" aria-label="Abrir menú">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navAdmin">
      <div class="navbar-nav me-auto mt-2 mt-md-0">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active fw-bold' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a class="nav-link {{ request()->routeIs('admin.pagos.*') ? 'active fw-bold' : '' }}" href="{{ route('admin.pagos.index') }}">Pagos</a>
        <a class="nav-link {{ request()->routeIs('admin.productos.*') ? 'active fw-bold' : '' }}" href="{{ route('admin.productos.index') }}">Productos</a>
      </div>
      <form method="POST" action="{{ route('admin.logout') }}" class="d-flex mt-2 mt-md-0">
        @csrf
        <button type="submit" class="btn btn-outline-light btn-sm">Cerrar sesión</button>
      </form>
    </div>
  </div>
</nav>
@endauth

<main class="container-fluid px-3 px-lg-4 py-4">
  @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
