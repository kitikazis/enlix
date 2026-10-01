<tr id="fila-producto-{{ $producto->id }}"
    data-id="{{ $producto->id }}"
    data-nombre="{{ mb_strtolower($producto->nombre) }}"
    data-referencia="{{ mb_strtolower($producto->referencia) }}"
    data-categoria-id="{{ $producto->categoria_id }}"
>
    <td class="py-2.5 pl-5 pr-3">
        <button type="button" class="block h-12 w-12 overflow-hidden rounded-nav border border-border" @click="lightboxUrl = @js($producto->imagen_url)">
            <img src="{{ $producto->imagen_thumb_url }}" alt="{{ $producto->nombre }}" class="h-full w-full object-cover">
        </button>
    </td>
    <td class="py-2.5 pr-3 font-medium text-text-primary">{{ $producto->nombre }}</td>
    <td class="py-2.5 pr-3 font-mono text-xs text-text-caption">{{ $producto->referencia }}</td>
    <td class="py-2.5 pr-3 text-text-secondary">{{ $producto->categoria?->nombre ?? 'Sin categoría' }}</td>
    <td class="py-2.5 pr-3">
        @php $totalEspecs = count($producto->especificaciones ?? []); @endphp
        @if ($totalEspecs > 0)
            <span class="rounded-full bg-page px-2 py-0.5 text-[11px] font-medium text-text-secondary">{{ $totalEspecs }}</span>
        @else
            <span class="text-xs text-text-caption">—</span>
        @endif
    </td>
    <td class="whitespace-nowrap py-2.5 pr-3">
        <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $producto->activo ? 'bg-pagado-bg text-pagado-text' : 'bg-anulado-bg text-anulado-text' }}">
            {{ $producto->activo ? 'Activo' : 'Inactivo' }}
        </span>
    </td>
    <td class="whitespace-nowrap py-2.5 pr-5 text-right">
        <button type="button" class="text-xs font-medium text-accent hover:text-accent-hover" @click="abrirEditar({{ $producto->id }})">Editar</button>
        <form method="POST" action="{{ route('admin.productos.alternar-activo', $producto) }}" class="inline">
            @csrf
            @method('PATCH')
            <button type="submit" class="ml-3 text-xs font-medium text-text-secondary hover:text-text-primary">
                {{ $producto->activo ? 'Desactivar' : 'Activar' }}
            </button>
        </form>
        {{--
            @submit, no onsubmit: el CSP del sitio no tiene 'unsafe-inline'
            en script-src, asi que un atributo onsubmit nativo quedaria
            bloqueado en silencio (el boton no haria nada). @submit de
            Alpine si funciona porque pasa por new Function(), que ya esta
            permitido para estas paginas (ver SecurityHeaders::$usaAlpineAdmin).
            El mensaje se arma entero en PHP y se pasa UNA sola vez por @js:
            concatenar el resultado de @js() dentro de otro string JS a mano
            rompe la sintaxis si el nombre trae comillas.
        --}}
        @php
            $mensajeEliminar = '¿Eliminar "'.$producto->nombre.'"? Desaparece del catálogo y del listado; no se puede deshacer desde aquí.';
        @endphp
        <form
            method="POST"
            action="{{ route('admin.productos.destroy', $producto) }}"
            class="inline"
            @submit="if (! confirm(@js($mensajeEliminar))) $event.preventDefault()"
        >
            @csrf
            @method('DELETE')
            <button type="submit" class="ml-3 text-xs font-medium text-rechazado-text hover:underline">
                Eliminar
            </button>
        </form>
    </td>
</tr>
