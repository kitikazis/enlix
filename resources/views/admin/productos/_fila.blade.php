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
    @php
        $resumen = [
            'imagen' => $producto->imagen_thumb_url,
            'nombre' => $producto->nombre,
            'referencia' => $producto->referencia,
            'categoria' => $producto->categoria?->nombre ?? 'Sin categoría',
        ];
    @endphp
    <td class="whitespace-nowrap py-2.5 pr-5 text-right">
        <button type="button" class="inline-flex min-h-[44px] items-center px-1.5 text-xs font-medium text-text-secondary hover:text-text-primary sm:min-h-0" @click="abrirVistaRapida({{ $producto->id }})">Ver</button>
        <button type="button" class="ml-1.5 inline-flex min-h-[44px] items-center px-1.5 text-xs font-medium text-accent hover:text-accent-hover sm:min-h-0" @click="abrirEditar({{ $producto->id }})">Editar</button>

        {{--
            Los forms (POST + @method) quedan como respaldo: la acción va por
            fetch (accionDeFila) y solo si falla la red se envía el form
            clásico. Nada de onsubmit/confirm() nativo: el CSP del sitio no
            tiene 'unsafe-inline' y lo bloquearía en silencio.
        --}}
        <form method="POST" action="{{ route('admin.productos.alternar-activo', $producto) }}" class="inline" x-ref="formActivar{{ $producto->id }}">
            @csrf
            @method('PATCH')
            <button
                type="button"
                class="ml-1.5 inline-flex min-h-[44px] items-center px-1.5 text-xs font-medium text-text-secondary hover:text-text-primary sm:min-h-0"
                @click="pedirConfirmacion({
                    titulo: {{ Illuminate\Support\Js::from($producto->activo ? 'Desactivar producto' : 'Activar producto') }},
                    resumen: {{ Illuminate\Support\Js::from($resumen) }},
                    mensaje: {{ Illuminate\Support\Js::from($producto->activo
                        ? 'Deja de verse en /productos. Puedes reactivarlo cuando quieras.'
                        : 'Vuelve a verse en /productos.') }},
                    textoBoton: {{ Illuminate\Support\Js::from($producto->activo ? 'Desactivar' : 'Activar') }},
                    peligroso: {{ $producto->activo ? 'true' : 'false' }},
                    accion: () => accionDeFila($refs.{{ 'formActivar'.$producto->id }}, 'activar'),
                })"
            >
                {{ $producto->activo ? 'Desactivar' : 'Activar' }}
            </button>
        </form>

        <form method="POST" action="{{ route('admin.productos.destroy', $producto) }}" class="inline" x-ref="formEliminar{{ $producto->id }}">
            @csrf
            @method('DELETE')
            <button
                type="button"
                class="ml-1.5 inline-flex min-h-[44px] items-center px-1.5 text-xs font-medium text-rechazado-text hover:underline sm:min-h-0"
                @click="pedirConfirmacion({
                    titulo: 'Eliminar producto',
                    resumen: {{ Illuminate\Support\Js::from($resumen) }},
                    mensaje: 'Sale del catálogo y del listado y va a la papelera. Desde allí puedes recuperarlo.',
                    textoBoton: 'Eliminar',
                    peligroso: true,
                    accion: () => accionDeFila($refs.{{ 'formEliminar'.$producto->id }}, 'eliminar', {{ $producto->id }}),
                })"
            >
                Eliminar
            </button>
        </form>
    </td>
</tr>
