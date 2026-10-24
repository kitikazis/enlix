<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="{{ $metaDescription ?? 'Enlix: mantenimiento preventivo y correctivo de laptops, PCs, All-in-One, Mac e impresoras de todas las marcas. Servicio 100 % presencial con cobertura a nivel nacional.' }}">
  <title>{{ $titulo ?? 'Enlix - Mantenimiento de computadoras e impresoras en Perú' }}</title>
@include('partials.seo')

  {{-- Favicons --}}
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon-32x32.png') }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/favicon-16x16.png') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/apple-touch-icon.png') }}">
  <link rel="shortcut icon" href="{{ asset('assets/img/favicon.png') }}" type="image/png">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ asset('assets/css/styles.css') }}?v=11" rel="stylesheet">
</head>
<body>

@include('partials.header')

@yield('content')

@include('partials.footer')

@include('components.modal-carrito-agregado')

@include('partials.cookies')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/enlix.js') }}?v=3"></script>
@stack('scripts')
</body>
</html>
