{{--
    Fila del listado de productos. Es un <tr> de x-admin.data-list: tabla
    desde lg, tarjeta por debajo (ver data-dl / data-label en admin.css).
    También la devuelve el backend como HTML para insertarla por JS
    (insertarFila en index.blade.php), así que no puede depender de nada
    del layout (ni $cspNonce ni x-data propio).
--}}
@php
    $totalEspecs = count($producto->especificaciones ?? []);
    $resumen = [
        'imagen' => $producto->imagen_thumb_url,
        'nombre' => $producto->nombre,
        'referencia' => $producto->referencia,
        'categoria' => $producto->categoria?->nombre ?? 'Sin categoría',
    ];
@endphp
<tr id="fila-producto-{{ $producto->id }}"
    data-id="{{ $producto->id }}"
    data-nombre="{{ mb_strtolower($producto->nombre) }}"
    data-referencia="{{ mb_strtolower($producto->referencia) }}"
    data-categoria-id="{{ $producto->categoria_id }}"
>
    <td data-dl="media" class="lg:w-[4.5rem]">
        <button type="button" class="block h-14 w-14 overflow-hidden rounded-nav border border-border bg-page lg:h-12 lg:w-12" @click="lightboxUrl = @js($producto->imagen_url)" aria-label="Ver imagen de {{ $producto->nombre }}">
            <img src="{{ $producto->imagen_thumb_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
        </button>
    </td>
    <td data-dl="title" class="min-w-0 font-medium text-text-primary">
        {{-- Etiquetas debajo del nombre: al lado lo aplastaban a una palabra por línea. --}}
        <span class="flex min-w-0 flex-col gap-1 lg:min-w-36">
            <span class="break-words">{{ $producto->nombre }}</span>
            @if ($producto->esPrueba())
                <x-admin.badge tone="prueba" :dot="false" class="self-start">Prueba</x-admin.badge>
            @endif
            @if ($producto->tieneOfertaVigente())
                <x-admin.badge tone="info" class="self-start whitespace-nowrap" title="Oferta del día en /productos">
                    Oferta · vence {{ $producto->oferta_termina_en->setTimezone('America/Lima')->format('d/m H:i') }}
                </x-admin.badge>
            @endif
        </span>
    </td>
    {{-- La referencia puede ser muy larga: se parte en líneas para no empujar las acciones fuera de la tabla. --}}
    <td data-label="Referencia" class="font-mono text-xs text-text-secondary"><span class="block break-words lg:max-w-40">{{ $producto->referencia }}</span></td>
    <td data-label="Categoría" class="text-text-secondary">{{ $producto->categoria?->nombre ?? 'Sin categoría' }}</td>
    {{-- Entre 1024 y 1279px es la columna que cede: sin ella las acciones entran sin scroll. --}}
    <td data-label="Especificaciones" class="lg:max-xl:hidden">
        @if ($totalEspecs > 0)
            <span class="count-badge">{{ $totalEspecs }}</span>
        @else
            <span class="text-xs text-text-secondary">—</span>
        @endif
    </td>
    <td data-dl="aside" class="whitespace-nowrap">
        <x-admin.badge :tone="$producto->activo ? 'success' : 'neutral'">{{ $producto->activo ? 'Activo' : 'Inactivo' }}</x-admin.badge>
    </td>
    <td data-dl="actions" data-align="right" class="whitespace-nowrap">
        <div class="row-actions">
            {{-- Editar: siempre visible (acción principal). El resto, en el menú "⋯" en móvil y en línea desde lg. --}}
            <button type="button" class="btn btn-secondary btn-sm text-accent max-lg:flex-1 lg:order-2 lg:border-transparent lg:bg-transparent lg:hover:bg-page" @click="abrirEditar({{ $producto->id }})">Editar</button>

            <div class="row-menu" data-row-menu>
                <button type="button" class="row-menu-toggle btn btn-secondary btn-sm btn-icon" aria-haspopup="true" aria-expanded="false" aria-label="Más acciones para {{ $producto->nombre }}">
                    <x-admin.icon name="mas" class="h-5 w-5" />
                </button>
                <div class="row-menu-backdrop" aria-hidden="true"></div>
                <div class="row-menu-panel" role="group" aria-label="Acciones de {{ $producto->nombre }}">
                    <p class="row-menu-title">{{ $producto->nombre }}</p>

                    <button type="button" class="btn btn-ghost btn-sm lg:order-1" @click="abrirVistaRapida({{ $producto->id }})">Ver</button>

                    {{--
                        Los forms (POST + @method) quedan como respaldo: la acción va por
                        fetch (accionDeFila) y solo si falla la red se envía el form
                        clásico. Nada de onsubmit/confirm() nativo: el CSP del sitio no
                        tiene 'unsafe-inline' y lo bloquearía en silencio.
                    --}}
                    <form method="POST" action="{{ route('admin.productos.alternar-activo', $producto) }}" class="lg:order-3" x-ref="formActivar{{ $producto->id }}">
                        @csrf
                        @method('PATCH')
                        <button
                            type="button"
                            class="btn btn-ghost btn-sm lg:order-3"
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

                    <form method="POST" action="{{ route('admin.productos.destroy', $producto) }}" class="lg:order-4" x-ref="formEliminar{{ $producto->id }}">
                        @csrf
                        @method('DELETE')
                        <button
                            type="button"
                            class="btn btn-ghost btn-sm text-rechazado-text hover:text-rechazado-text lg:order-4"
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

                    <button type="button" class="row-menu-cancel btn btn-secondary btn-sm mt-1 justify-center">Cancelar</button>
                </div>
            </div>
        </div>
    </td>
</tr>
