@extends('layouts.app')

@section('content')

@php
    [$contenidoHtml, $indice] = $pagina->contenidoConIndice();
@endphp

<style>
  .legal-pagina { max-width: 820px; }
  .legal-pagina h1 { margin-bottom: 4px; }
  .legal-actualizado { color: var(--enlix-muted); font-size: 13.5px; margin-bottom: 28px; }

  .legal-indice {
    background: var(--enlix-bg-soft);
    border: 1px solid var(--enlix-border);
    border-radius: 8px;
    padding: 16px 20px;
    margin-bottom: 32px;
  }
  .legal-indice summary {
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    color: var(--enlix-ink);
    list-style: none;
  }
  .legal-indice summary::-webkit-details-marker { display: none; }
  .legal-indice summary::after {
    content: '▾';
    float: right;
    transition: transform .15s ease;
  }
  .legal-indice[open] summary::after { transform: rotate(180deg); }
  .legal-indice ol {
    margin: 14px 0 0;
    padding-left: 20px;
  }
  .legal-indice li { margin: 0; padding: 0; }
  .legal-indice li.nivel-3 { list-style: none; margin-left: -4px; }
  .legal-indice a {
    display: inline-block;
    padding: 6px 0;
    font-size: 15px;
    color: var(--enlix-blue-700);
  }
  .legal-indice a:hover { color: var(--enlix-blue-900); }

  /* Texto legal: 16px base (sin zoom automatico en iOS) e interlineado comodo. */
  .legal-contenido { color: var(--enlix-ink); font-size: 16px; line-height: 1.75; }
  .legal-contenido h2 { font-size: 21px; margin: 36px 0 14px; scroll-margin-top: 90px; }
  .legal-contenido h2:first-child { margin-top: 0; }
  .legal-contenido h3 { font-size: 17.5px; margin: 24px 0 10px; scroll-margin-top: 90px; }
  .legal-contenido p { margin: 0 0 14px; }
  .legal-contenido ul, .legal-contenido ol { margin: 0 0 14px; padding-left: 22px; }
  .legal-contenido a { color: var(--enlix-blue-700); }
  .legal-contenido blockquote {
    margin: 0 0 14px; padding: 12px 16px; border-left: 3px solid var(--enlix-border);
    color: var(--enlix-muted); font-style: italic;
  }
</style>

<section class="section-pad">
  <div class="container legal-pagina">

    <p class="svc-block-num">Legal</p>
    <h1>{{ $pagina->titulo }}</h1>
    <p class="legal-actualizado">Última actualización: {{ $pagina->updated_at->setTimezone('America/Lima')->translatedFormat('d \d\e F \d\e Y') }}</p>

    @if (count($indice) > 1)
      <details class="legal-indice" open>
        <summary>Índice de secciones</summary>
        <ol>
          @foreach ($indice as $item)
            <li class="{{ $item['nivel'] === 3 ? 'nivel-3' : '' }}"><a href="#{{ $item['id'] }}">{{ $item['texto'] }}</a></li>
          @endforeach
        </ol>
      </details>
    @endif

    <div class="legal-contenido">
      {!! $contenidoHtml !!}
    </div>

  </div>
</section>

@endsection
