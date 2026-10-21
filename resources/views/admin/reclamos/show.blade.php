@php
    use App\Enums\EstadoReclamo;

    $pendiente = $reclamo->estado === EstadoReclamo::Pendiente;
    $dt = 'text-xs font-medium text-text-secondary';
    $dd = 'mt-0.5 break-words text-sm text-text-primary';
@endphp

<x-layouts.admin-dashboard :titulo="$titulo">
    {{-- x-data en la columna (no en un div propio): así el botón "Responder" es hijo directo y su sticky abarca toda la página. --}}
    <div
        class="mx-auto flex w-full max-w-3xl flex-col gap-5 sm:gap-6"
        x-data="{ abierto: {{ $errors->has('respuesta') ? 'true' : 'false' }} }"
        @keydown.escape.window="abierto = false"
    >

        <x-admin.page-header
            title="N.° {{ $reclamo->numero_correlativo }} · {{ $reclamo->tipo->etiqueta() }}"
            :back="route('admin.reclamos.index')"
            back-label="Reclamos"
        >
            <x-slot:actions>
                <x-admin.badge :tone="$pendiente ? 'warning' : 'success'">{{ $reclamo->estado->etiqueta() }}</x-admin.badge>
            </x-slot:actions>
        </x-admin.page-header>

        @if (session('status'))
            <div class="rounded-nav border border-pagado-dot bg-pagado-bg px-4 py-3 text-sm text-pagado-text" role="status">
                {{ session('status') }}
            </div>
        @endif

        <x-admin.card title="Consumidor">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2">
                <div><dt class="{{ $dt }}">Nombre</dt><dd class="{{ $dd }}">{{ $reclamo->nombre_consumidor }}</dd></div>
                <div><dt class="{{ $dt }}">Documento</dt><dd class="{{ $dd }} tabular-nums">{{ $reclamo->tipo_documento_consumidor }} {{ $reclamo->numero_documento_consumidor }}</dd></div>
                <div><dt class="{{ $dt }}">Domicilio</dt><dd class="{{ $dd }}">{{ $reclamo->domicilio_consumidor }}</dd></div>
                <div><dt class="{{ $dt }}">Teléfono</dt><dd class="{{ $dd }} tabular-nums">{{ $reclamo->telefono_consumidor }}</dd></div>
                <div class="md:col-span-2"><dt class="{{ $dt }}">Correo</dt><dd class="{{ $dd }}">{{ $reclamo->correo_consumidor }}</dd></div>
                @if ($reclamo->es_menor_edad)
                    <div class="rounded-nav bg-page p-3 md:col-span-2">
                        <dt class="{{ $dt }}">Padre/tutor</dt>
                        <dd class="{{ $dd }}">{{ $reclamo->nombre_padre_tutor }} · {{ $reclamo->documento_padre_tutor }}</dd>
                    </div>
                @endif
            </dl>
        </x-admin.card>

        <x-admin.card title="Producto o servicio">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2">
                <div><dt class="{{ $dt }}">Tipo</dt><dd class="{{ $dd }}">{{ $reclamo->tipo_bien->etiqueta() }}</dd></div>
                @if ($reclamo->monto_centimos)
                    <div><dt class="{{ $dt }}">Monto</dt><dd class="{{ $dd }} font-mono tabular-nums">S/ {{ number_format($reclamo->monto_centimos / 100, 2) }}</dd></div>
                @endif
                <div class="md:col-span-2"><dt class="{{ $dt }}">Descripción</dt><dd class="{{ $dd }}">{{ $reclamo->descripcion_bien }}</dd></div>
            </dl>
        </x-admin.card>

        <x-admin.card title="Detalle">
            <p class="mb-4 whitespace-pre-line break-words text-sm text-text-primary">{{ $reclamo->detalle }}</p>
            <p class="mb-1 {{ $dt }}">Pedido del consumidor</p>
            <p class="whitespace-pre-line break-words text-sm text-text-primary">{{ $reclamo->pedido_consumidor }}</p>
        </x-admin.card>

        <x-admin.card title="Plazos">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2">
                <div><dt class="{{ $dt }}">Registrado</dt><dd class="{{ $dd }} tabular-nums">{{ $reclamo->created_at->setTimezone('America/Lima')->translatedFormat('d/m/Y H:i') }}</dd></div>
                <div><dt class="{{ $dt }}">Plazo de respuesta (15 días hábiles)</dt><dd class="{{ $dd }} tabular-nums">{{ $reclamo->fecha_limite_respuesta->translatedFormat('d/m/Y') }}</dd></div>
                <div class="md:col-span-2"><dt class="{{ $dt }}">Aceptó la política de privacidad</dt><dd class="{{ $dd }} tabular-nums">{{ $reclamo->acepto_privacidad_en->setTimezone('America/Lima')->translatedFormat('d/m/Y H:i') }}</dd></div>
            </dl>
        </x-admin.card>

        @if (! $pendiente)
            <x-admin.card title="Respuesta al consumidor">
                @if ($reclamo->respuesta)
                    <p class="whitespace-pre-line break-words text-sm text-text-primary">{{ $reclamo->respuesta }}</p>
                    <p class="mt-3 text-xs text-text-secondary">
                        Respondido el {{ $reclamo->respondido_en->setTimezone('America/Lima')->translatedFormat('d/m/Y H:i') }}
                        @if ($reclamo->respondido_en->startOfDay()->gt($reclamo->fecha_limite_respuesta))
                            · <span class="font-medium text-rechazado-text">fuera del plazo ({{ $reclamo->fecha_limite_respuesta->translatedFormat('d/m/Y') }})</span>
                        @else
                            · dentro del plazo
                        @endif
                    </p>
                @else
                    <p class="text-sm text-text-secondary">Se marcó como atendido antes de que existiera el registro de respuestas.</p>
                @endif
            </x-admin.card>
        @else
            {{-- Botón de acción: fijo al pie en móvil (siempre a mano), en línea desde sm. --}}
            <div class="sticky bottom-0 z-20 -mx-4 -mb-5 border-t border-border bg-card px-4 py-3 sm:static sm:mx-0 sm:mb-0 sm:flex sm:justify-end sm:border-0 sm:bg-transparent sm:p-0">
                <button type="button" @click="abierto = true; $nextTick(() => $refs.respuesta.focus())" class="btn btn-primary w-full sm:w-auto">
                    Responder y marcar como atendido
                </button>
            </div>

            <div x-show="abierto" x-cloak class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="titulo-responder">
                    <div class="absolute inset-0 bg-black/50" @click="abierto = false" aria-hidden="true"></div>
                    <form method="POST" action="{{ route('admin.reclamos.atender', $reclamo) }}" class="relative flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-2xl bg-card shadow-pop sm:max-w-lg sm:rounded-2xl">
                        @csrf
                        @method('PATCH')
                        <div class="shrink-0 border-b border-border px-5 py-4">
                            <h2 id="titulo-responder" class="text-base font-semibold text-text-primary">Responder reclamo N.° {{ $reclamo->numero_correlativo }}</h2>
                            <p class="mt-1 text-xs text-text-secondary">Plazo hasta el {{ $reclamo->fecha_limite_respuesta->translatedFormat('d/m/Y') }}. Esta acción no se puede deshacer.</p>
                        </div>
                        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                            <label for="respuesta" class="mb-1.5 block text-sm font-medium text-text-primary">Respuesta dada al consumidor</label>
                            <textarea
                                id="respuesta" name="respuesta" x-ref="respuesta" rows="7" maxlength="5000" required
                                class="field-input"
                                @error('respuesta') aria-invalid="true" aria-describedby="error-respuesta" @enderror
                                placeholder="Qué se respondió, por qué medio y qué solución se ofreció."
                            >{{ old('respuesta') }}</textarea>
                            @error('respuesta')
                                <p id="error-respuesta" class="mt-1 text-xs text-rechazado-text">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-border px-5 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:flex-row sm:justify-end sm:pb-3">
                            <button type="button" @click="abierto = false" class="btn btn-secondary">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar y marcar atendido</button>
                        </div>
                    </form>
                </div>
        @endif

    </div>
</x-layouts.admin-dashboard>
