@php
  $_seo_titulo      = $titulo ?? 'Enlix - Mantenimiento de computadoras e impresoras en Perú';
  $_seo_descripcion = $metaDescription ?? 'Enlix: mantenimiento preventivo y correctivo de laptops, PCs, All-in-One, Mac e impresoras de todas las marcas. Servicio 100 % presencial con cobertura a nivel nacional.';
  $_seo_imagen      = $svc['image'] ?? asset('assets/img/android-chrome-192x192.png');
  $_seo_empresa_id  = route('inicio').'#empresa';

  // Datos estructurados (schema.org) para que Google entienda qué es Enlix.
  $_seo_jsonld = [];

  if (($current ?? '') === 'inicio' || ($current ?? '') === 'contacto') {
      $_seo_jsonld[] = [
          '@context'    => 'https://schema.org',
          '@type'       => 'ProfessionalService',
          '@id'         => $_seo_empresa_id,
          'name'        => 'Enlix',
          'url'         => route('inicio'),
          'logo'        => asset('assets/img/android-chrome-192x192.png'),
          'image'       => asset('assets/img/android-chrome-192x192.png'),
          'description' => 'Empresa peruana especializada en el mantenimiento preventivo y correctivo de equipos de cómputo e impresoras de todas las marcas, con servicio 100 % presencial y cobertura a nivel nacional.',
          'telephone'   => \App\Support\Contacto::TELEFONO_TEL,
          'email'       => 'servicios@enlix.pe',
          'address'     => ['@type' => 'PostalAddress', 'addressLocality' => 'Lima', 'addressCountry' => 'PE'],
          'areaServed'  => ['@type' => 'Country', 'name' => 'Perú'],
          'openingHoursSpecification' => [[
              '@type'     => 'OpeningHoursSpecification',
              'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
              'opens'     => '09:00',
              'closes'    => '18:00',
          ]],
          'sameAs'          => ['https://www.linkedin.com/company/Enlix/'],
          'hasOfferCatalog' => [
              '@type'           => 'OfferCatalog',
              'name'            => 'Servicios de Enlix',
              'itemListElement' => collect(\App\Support\Catalogo::items())->map(fn ($s, $slug) => [
                  '@type'       => 'Offer',
                  'itemOffered' => ['@type' => 'Service', 'name' => $s['title'], 'url' => route('servicio.show', $slug)],
              ])->values()->all(),
          ],
      ];
  }

  if (isset($svc, $servicio_actual)) {
      $_seo_jsonld[] = [
          '@context'    => 'https://schema.org',
          '@type'       => 'Service',
          'name'        => $svc['title'],
          'serviceType' => $svc['short'],
          'description' => $svc['lead'],
          'url'         => route('servicio.show', $servicio_actual),
          'image'       => $svc['image'],
          'areaServed'  => ['@type' => 'Country', 'name' => 'Perú'],
          'provider'    => ['@type' => 'ProfessionalService', '@id' => $_seo_empresa_id, 'name' => 'Enlix', 'url' => route('inicio')],
      ];
      $_seo_jsonld[] = [
          '@context'        => 'https://schema.org',
          '@type'           => 'BreadcrumbList',
          'itemListElement' => [
              ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => route('inicio')],
              ['@type' => 'ListItem', 'position' => 2, 'name' => $svc['short'], 'item' => route('servicio.show', $servicio_actual)],
          ],
      ];
  }
@endphp
  <link rel="canonical" href="{{ url()->current() }}">

  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Enlix">
  <meta property="og:locale" content="es_PE">
  <meta property="og:title" content="{{ $_seo_titulo }}">
  <meta property="og:description" content="{{ $_seo_descripcion }}">
  <meta property="og:url" content="{{ url()->current() }}">
  <meta property="og:image" content="{{ $_seo_imagen }}">
  <meta name="twitter:card" content="{{ isset($svc['image']) ? 'summary_large_image' : 'summary' }}">

@if (config('services.google.site_verification'))
  <meta name="google-site-verification" content="{{ config('services.google.site_verification') }}">
@endif
@foreach ($_seo_jsonld as $_bloque)
  <script type="application/ld+json" nonce="{{ $cspNonce ?? '' }}">{!! json_encode($_bloque, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endforeach
