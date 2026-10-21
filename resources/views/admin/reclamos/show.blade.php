<x-layouts.admin-dashboard :titulo="$titulo">
    <div class="mx-auto flex w-full max-w-[760px] flex-col gap-4">

        <div>
            <a href="{{ route('admin.reclamos.index') }}" class="text-sm text-text-secondary hover:text-text-primary">← Reclamos</a>
            <div class="mt-1 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-semibold text-text-primary">
                    N.° {{ $reclamo->numero_correlativo }} · {{ $reclamo->tipo->etiqueta() }}
                </h1>
                <span class="rounded-full px-2.5 py-1 text-[11px] font-medium {{ $reclamo->estado->badgeClasses() }}">
                    {{ $reclamo->estado->etiqueta() }}
                </span>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-nav border border-pagado-dot bg-pagado-bg px-4 py-3 text-sm text-pagado-text">
                {{ session('status') }}
            </div>
        @endif

        <x-admin.card title="Consumidor">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                <div><dt class="text-text-caption">Nombre</dt><dd class="text-text-primary">{{ $reclamo->nombre_consumidor }}</dd></div>
                <div><dt class="text-text-caption">Documento</dt><dd class="text-text-primary">{{ $reclamo->tipo_documento_consumidor }} {{ $reclamo->numero_documento_consumidor }}</dd></div>
                <div><dt class="text-text-caption">Domicilio</dt><dd class="text-text-primary">{{ $reclamo->domicilio_consumidor }}</dd></div>
                <div><dt class="text-text-caption">Teléfono</dt><dd class="text-text-primary">{{ $reclamo->telefono_consumidor }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-text-caption">Correo</dt><dd class="text-text-primary">{{ $reclamo->correo_consumidor }}</dd></div>
                @if ($reclamo->es_menor_edad)
                    <div class="sm:col-span-2 rounded-nav bg-page p-3">
                        <dt class="text-text-caption">Padre/tutor</dt>
                        <dd class="text-text-primary">{{ $reclamo->nombre_padre_tutor }} · {{ $reclamo->documento_padre_tutor }}</dd>
                    </div>
                @endif
            </dl>
        </x-admin.card>

        <x-admin.card title="Producto o servicio">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                <div><dt class="text-text-caption">Tipo</dt><dd class="text-text-primary">{{ $reclamo->tipo_bien->etiqueta() }}</dd></div>
                @if ($reclamo->monto_centimos)
                    <div><dt class="text-text-caption">Monto</dt><dd class="text-text-primary">S/ {{ number_format($reclamo->monto_centimos / 100, 2) }}</dd></div>
                @endif
                <div class="sm:col-span-2"><dt class="text-text-caption">Descripción</dt><dd class="text-text-primary">{{ $reclamo->descripcion_bien }}</dd></div>
            </dl>
        </x-admin.card>

        <x-admin.card title="Detalle">
            <p class="mb-3 whitespace-pre-line text-sm text-text-primary">{{ $reclamo->detalle }}</p>
            <p class="mb-1 text-xs font-medium text-text-caption">Pedido del consumidor</p>
            <p class="whitespace-pre-line text-sm text-text-primary">{{ $reclamo->pedido_consumidor }}</p>
        </x-admin.card>

        <x-admin.card title="Plazos">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                <div><dt class="text-text-caption">Registrado</dt><dd class="text-text-primary">{{ $reclamo->created_at->setTimezone('America/Lima')->translatedFormat('d/m/Y H:i') }}</dd></div>
                <div><dt class="text-text-caption">Plazo de respuesta (15 días hábiles)</dt><dd class="text-text-primary">{{ $reclamo->fecha_limite_respuesta->translatedFormat('d/m/Y') }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-text-caption">Aceptó la política de privacidad</dt><dd class="text-text-primary">{{ $reclamo->acepto_privacidad_en->setTimezone('America/Lima')->translatedFormat('d/m/Y H:i') }}</dd></div>
            </dl>
        </x-admin.card>

        @if ($reclamo->estado === \App\Enums\EstadoReclamo::Atendido)
            <x-admin.card title="Respuesta al consumidor">
                @if ($reclamo->respuesta)
                    <p class="whitespace-pre-line break-words text-sm text-text-primary">{{ $reclamo->respuesta }}</p>
                    <p class="mt-3 text-xs text-text-caption">
                        Respondido el {{ $reclamo->respondido_en->setTimezone('America/Lima')->translatedFormat('d/m/Y H:i') }}
                        @if ($reclamo->respondido_en->startOfDay()->gt($reclamo->fecha_limite_respuesta))
                            · <span class="font-medium text-rechazado-text">fuera del plazo ({{ $reclamo->fecha_limite_respuesta->translatedFormat('d/m/Y') }})</span>
                        @else
                            · dentro del plazo
                        @endif
                    </p>
                @else
                    <p class="text-sm text-text-caption">Se marcó como atendido antes de que existiera el registro de respuestas.</p>
                @endif
            </x-admin.card>
        @else
            <div x-data="{ abierto: {{ $errors->has('respuesta') ? 'true' : 'false' }} }" @keydown.escape.window="abierto = false" class="flex justify-end">
                <button type="button" @click="abierto = true; $nextTick(() => $refs.respuesta.focus())" class="inline-flex h-11 w-full items-center justify-center rounded-nav bg-accent px-6 text-sm font-medium text-white hover:bg-accent-hover sm:w-auto">
                    Responder y marcar como atendido
                </button>

                <div x-show="abierto" x-cloak class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="titulo-responder">
                    <div class="absolute inset-0 bg-black/40" @click="abierto = false"></div>
                    <form method="POST" action="{{ route('admin.reclamos.atender', $reclamo) }}" class="relative flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-2xl bg-card shadow-xl sm:max-w-lg sm:rounded-2xl">
                        @csrf
                        @method('PATCH')
                        <div class="shrink-0 border-b border-border px-5 py-4">
                            <h2 id="titulo-responder" class="text-base font-semibold text-text-primary">Responder reclamo N.° {{ $reclamo->numero_correlativo }}</h2>
                            <p class="mt-1 text-xs text-text-caption">Plazo hasta el {{ $reclamo->fecha_limite_respuesta->translatedFormat('d/m/Y') }}. Esta acción no se puede deshacer.</p>
                        </div>
                        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                            <label for="respuesta" class="mb-1 block text-sm font-medium text-text-primary">Respuesta dada al consumidor</label>
                            <textarea
                                id="respuesta" name="respuesta" x-ref="respuesta" rows="7" maxlength="5000" required
                                class="w-full rounded-nav border bg-card px-3 py-2 text-base sm:text-sm {{ $errors->has('respuesta') ? 'border-rechazado-text' : 'border-border' }}"
                                placeholder="Qué se respondió, por qué medio y qué solución se ofreció."
                            >{{ old('respuesta') }}</textarea>
                            @error('respuesta')
                                <p class="mt-1 text-xs text-rechazado-text">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-border px-5 py-3 sm:flex-row sm:justify-end">
                            <button type="button" @click="abierto = false" class="inline-flex min-h-[44px] items-center justify-center rounded-nav border border-border px-4 text-sm font-medium text-text-secondary hover:bg-page sm:h-10 sm:min-h-0">Cancelar</button>
                            <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-nav bg-accent px-4 text-sm font-medium text-white hover:bg-accent-hover sm:h-10 sm:min-h-0">Guardar y marcar atendido</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    </div>
</x-layouts.admin-dashboard>
