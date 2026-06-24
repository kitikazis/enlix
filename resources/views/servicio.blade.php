@extends('layouts.app')

@section('content')

<section class="section-pad-sm bg-soft" style="border-bottom: 1px solid var(--enlix-border);">
  <div class="container">
    <p class="crumbs" style="font-size: 13px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--enlix-muted); margin: 0;">
      <a href="{{ route('inicio') }}" style="color: var(--enlix-muted);">Inicio</a> &nbsp;/&nbsp;
      <a href="#" style="color: var(--enlix-muted);">Servicios</a> &nbsp;/&nbsp;
      <a href="#" style="color: var(--enlix-muted);">{{ $svc['group_label'] }}</a> &nbsp;/&nbsp;
      <span style="color: var(--enlix-ink);">{{ $svc['short'] }}</span>
    </p>
  </div>
</section>

<section class="section-pad-sm">
  <div class="container">
    <div class="row gx-lg-5">

      {{-- Sidebar --}}
      <div class="col-lg-4 mb-4 mb-lg-0">
        @include('partials.sidebar-servicios', ['servicio_actual' => $servicio_actual])
      </div>

      {{-- Contenido --}}
      <div class="col-lg-8">

        <div class="svc-hero-img">
          <img src="{{ $svc['image'] }}" alt="{{ $svc['title'] }}">
          <div class="svc-hero-overlay">
            <div>
              <span class="badge-svc">{{ $svc['badge'] }}</span>
              <h1>{{ $svc['title'] }}</h1>
            </div>
          </div>
        </div>

        <p style="font-size: 17px; line-height: 1.7;">
          {{ $svc['lead'] }}
        </p>

        <div class="svc-block mt-4" id="detalle">
          <p class="svc-block-num">{{ $svc['group_num'] }} — {{ $svc['group_label'] }}</p>
          <h2>Qué incluye este servicio</h2>
          <ul>
            @foreach ($svc['bullets'] as $b)
              <li>{{ $b }}</li>
            @endforeach
          </ul>
        </div>

        {{-- Otros servicios del grupo --}}
        @php($otros = \App\Support\Catalogo::otrosDelGrupo($servicio_actual))
        @if (! empty($otros))
        <div class="svc-related mt-5">
          <p class="svc-related-title">Otros servicios de {{ $svc['group_label'] }}</p>
          <div class="row g-3">
            @foreach ($otros as $o)
              <div class="col-md-6">
                <a href="{{ route('servicio.show', $o['slug']) }}" class="svc-related-card">
                  <span class="svc-related-card-label">{{ $o['short'] }}</span>
                  <span class="svc-related-card-arrow" aria-hidden="true">&rarr;</span>
                </a>
              </div>
            @endforeach
          </div>
        </div>
        @endif

        {{-- CTA --}}
        <div class="mt-5 p-4 p-md-5" style="background: var(--enlix-blue-900); color: #fff; border-radius: 6px;">
          <div class="row align-items-center">
            <div class="col-md-8">
              <h3 style="color: #fff; font-size: 22px; margin: 0 0 6px;">{{ $svc['cta_title'] }}</h3>
              <p style="color: rgba(255,255,255,0.75); margin: 0;">{{ $svc['cta_text'] }}</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
              <a href="{{ route('contacto') }}" class="btn btn-primary-enlix" style="background: #fff; color: var(--enlix-blue-900); border-color: #fff;">{{ $svc['cta_btn'] }}</a>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

@endsection
