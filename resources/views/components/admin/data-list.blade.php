@props(['columns' => [], 'caption' => null, 'bodyId' => null, 'scroll' => null])

{{--
    Tabla responsive del admin: tabla real desde lg (header fijo, hover de
    fila) y tarjetas apiladas por debajo, con UNA sola marca (ver
    .data-list en admin.css). Cada fila es un <tr> normal; cada <td> lleva:
      - data-label="Producto" -> se muestra como etiqueta en la tarjeta
      - data-dl="title|aside|status|media|actions" -> su lugar en la tarjeta
      - data-dl="mobile" -> par que solo existe en la tarjeta (en la tabla
        ese dato va dentro de otra columna)

    columns: ['Fecha', ['label' => 'Monto', 'align' => 'right'], ['label' => 'Acciones', 'sr' => true]]
             'class' agrega clases al <th> (ej. ocultar la columna en un rango de anchos).
    scroll: null (sin alto fijo) | 'y' (alto máximo + header fijo) | 'fill'
            (ocupa el alto de una x-admin.card fill).
--}}
<div class="dl-wrap" @if ($scroll) data-scroll="{{ $scroll }}" @endif>
    <table {{ $attributes->class(['data-list']) }}>
        @if ($caption)
            <caption class="sr-only">{{ $caption }}</caption>
        @endif
        <thead>
            <tr>
                @foreach ($columns as $columna)
                    @php $columna = is_array($columna) ? $columna : ['label' => $columna]; @endphp
                    <th scope="col" @if (($columna['align'] ?? null) === 'right') data-align="right" @endif @if ($columna['class'] ?? null) class="{{ $columna['class'] }}" @endif>
                        @if ($columna['sr'] ?? false)
                            <span class="sr-only">{{ $columna['label'] }}</span>
                        @else
                            {{ $columna['label'] }}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody @if ($bodyId) id="{{ $bodyId }}" @endif>
            {{ $slot }}
        </tbody>
    </table>
</div>
