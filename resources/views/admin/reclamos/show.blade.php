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

        @if ($reclamo->estado === \App\Enums\EstadoReclamo::Pendiente)
            <form method="POST" action="{{ route('admin.reclamos.atender', $reclamo) }}" class="flex justify-end">
                @csrf
                @method('PATCH')
                <button type="submit" class="inline-flex h-11 items-center justify-center rounded-nav bg-accent px-6 text-sm font-medium text-white hover:bg-accent-hover">
                    Marcar como atendido
                </button>
            </form>
        @endif

    </div>
</x-layouts.admin-dashboard>
