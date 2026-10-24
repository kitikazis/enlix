{{--
  Aviso de cookies + Google Analytics 4. Solo existe si GOOGLE_ANALYTICS_ID
  está configurado; Analytics se carga recién cuando el visitante acepta
  (Ley 29733). Lógica en public/assets/js/cookies.js.
--}}
@if (config('services.google.analytics_id'))
<div id="cookies-aviso" class="cookies-aviso" role="dialog" aria-label="Aviso de cookies"
     data-ga-id="{{ config('services.google.analytics_id') }}" hidden>
  <p class="cookies-aviso-texto">
    Usamos cookies de analítica para saber cómo se usa la web y mejorarla.
    Solo se activan si las aceptas.
    <a href="{{ route('politica-privacidad') }}">Más información</a>
  </p>
  <div class="cookies-aviso-acciones">
    <button type="button" class="btn btn-outline-enlix" data-cookies="rechazar">Rechazar</button>
    <button type="button" class="btn btn-primary-enlix" data-cookies="aceptar">Aceptar</button>
  </div>
</div>
<script src="{{ asset('assets/js/cookies.js') }}?v=1" defer></script>
@endif
