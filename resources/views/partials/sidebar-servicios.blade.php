@php
  $servicio_actual = $servicio_actual ?? '';
  $grupo_activo    = config("servicios.items.$servicio_actual.group_slug", '');
@endphp
<aside class="svc-sidebar">
  <p class="sidebar-title">Nuestros servicios</p>
  <nav>
    @foreach ($grupos as $g)
      @php($is_open = ($grupo_activo === $g['slug']))
      <div class="svc-group-nav {{ $is_open ? 'open' : '' }}">
        <button class="svc-group-head-row" type="button">
          <span class="svc-group-num">{{ $g['num'] }}</span>
          <span class="svc-group-label">{{ $g['label'] }}</span>
        </button>
        <ul class="svc-subnav">
          @foreach ($g['items'] as $sub)
            <li>
              <a class="{{ $servicio_actual === $sub['slug'] ? 'active' : '' }}"
                 href="{{ route('servicio.show', $sub['slug']) }}">
                {{ $sub['short'] }}
              </a>
            </li>
          @endforeach
        </ul>
      </div>
    @endforeach
  </nav>

  <div class="sidebar-cta">
    <p>¿Necesitas atención para tus equipos?</p>
    <a href="{{ $servicio_actual !== '' ? \App\Support\Contacto::whatsappServicio(config("servicios.items.$servicio_actual.short", '')) : \App\Support\Contacto::whatsapp() }}"
       target="_blank" rel="noopener" class="btn btn-whatsapp-enlix w-100">Cotizar por WhatsApp</a>
  </div>
</aside>
<script nonce="{{ $cspNonce }}">
(function () {
  var groups = document.querySelectorAll('.svc-sidebar .svc-group-nav');
  groups.forEach(function (group) {
    var btn = group.querySelector('.svc-group-head-row');
    btn.addEventListener('click', function () {
      var isOpen = group.classList.contains('open');
      groups.forEach(function (g) { g.classList.remove('open'); });
      if (!isOpen) group.classList.add('open');
    });
  });
})();
</script>
