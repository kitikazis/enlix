{{-- Tarjeta de producto ($p = array de App\Support\Producto). Toda la tarjeta
     lleva al detalle (stretched-link del título); el botón queda por encima. --}}
<div class="prod-card">
  <img src="{{ $p['imagen_url'] }}" alt="{{ $p['nombre'] }}" class="prod-card-img" loading="lazy">
  <h3 class="prod-card-title">
    <a href="{{ route('productos.show', $p['slug']) }}" class="stretched-link">{{ $p['nombre'] }}</a>
  </h3>
  {{-- descripcion ya pasa por HTMLPurifier al guardarse (ver
       Producto::descripcion() en el modelo, solo permite
       p/br/strong/b/em/i/u/ul/ol/li/a), por eso es seguro
       mostrarla como HTML en vez de texto escapado. --}}
  <div class="prod-card-desc">{!! $p['descripcion'] !!}</div>

  @if (! empty($p['features']))
    <ul class="prod-card-list">
      @foreach ($p['features'] as $f)
        <li>{{ $f }}</li>
      @endforeach
    </ul>
  @endif

  <div class="prod-card-price">
    <span class="currency">S/</span> {{ number_format($p['precio_centimos'] / 100, 2) }}
  </div>

  @if ($p['stock_disponible'] > 0)
    <button type="button"
            class="btn btn-primary-enlix w-100 btn-agregar-carrito"
            data-producto-id="{{ $p['id'] }}"
            data-imagen-url="{{ $p['imagen_url'] }}"
            @if ($p['precio_comparacion_centimos']) data-precio-comparacion-centimos="{{ $p['precio_comparacion_centimos'] }}" @endif>
      Agregar al carrito
    </button>
  @else
    <button type="button" class="btn btn-primary-enlix w-100" disabled>Agotado</button>
  @endif
</div>
