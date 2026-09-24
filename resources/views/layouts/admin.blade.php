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
<nav class="navbar navbar-dark navbar-expand" style="background:#0b2447;">
  <div class="container">
    <span class="navbar-brand mb-0 h1">Enlix · Admin</span>
    <div class="navbar-nav me-auto">
      <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active fw-bold' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
      <a class="nav-link {{ request()->routeIs('admin.pagos.*') ? 'active fw-bold' : '' }}" href="{{ route('admin.pagos.index') }}">Pagos</a>
      <a class="nav-link {{ request()->routeIs('admin.productos.*') ? 'active fw-bold' : '' }}" href="{{ route('admin.productos.index') }}">Productos</a>
    </div>
    <form method="POST" action="{{ route('admin.logout') }}" class="d-flex">
      @csrf
      <button type="submit" class="btn btn-outline-light btn-sm">Cerrar sesión</button>
    </form>
  </div>
</nav>
@endauth

<main class="container py-4">
  @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
