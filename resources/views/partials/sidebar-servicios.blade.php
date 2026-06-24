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
    <p>¿Necesitas asesoría para tu proyecto?</p>
    <a href="{{ route('contacto') }}" class="btn btn-primary-enlix w-100">Solicitar cotización</a>
  </div>
</aside>
<script>
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
