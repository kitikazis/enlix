<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titulo ?? 'Dashboard - Enlix Admin' }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/img/favicon.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite('resources/css/admin.css')
    @stack('head')
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
    <script>
        // Alpine global: fade inferior en listas/tablas con scroll interno
        // propio (ver .dash-scroll-fade en admin.css). Solo se activa cuando
        // de verdad queda contenido por debajo del borde visible.
        function scrollFade() {
            return {
                desbordado: false,
                actualizarFade() {
                    const el = this.$el;
                    this.desbordado = el.scrollHeight - el.scrollTop - el.clientHeight > 4;
                },
            };
        }
    </script>
</head>
<body class="h-full bg-page font-sans text-text-primary antialiased {{ ($sinScroll ?? false) ? 'overflow-hidden' : '' }}">

@php
    $enlacesMenu = [
        ['url' => route('admin.dashboard'), 'activo' => request()->routeIs('admin.dashboard'), 'label' => 'Dashboard', 'icono' => 'dashboard'],
        ['url' => route('admin.pagos'), 'activo' => request()->routeIs('admin.pagos'), 'label' => 'Pagos', 'badge' => $badgeAtencion ?? null, 'icono' => 'pagos'],
        ['url' => route('admin.pedidos.index'), 'activo' => request()->routeIs('admin.pedidos.*'), 'label' => 'Pedidos', 'icono' => 'pedidos'],
        ['url' => route('admin.productos.index'), 'activo' => request()->routeIs('admin.productos.*'), 'label' => 'Productos', 'icono' => 'productos'],
    ];

    $iconosMenu = [
        'dashboard' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75a1 1 0 0 1 1-1h4.5a1 1 0 0 1 1 1v4.5a1 1 0 0 1-1 1h-4.5a1 1 0 0 1-1-1v-4.5Zm0 8.5a1 1 0 0 1 1-1h4.5a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-4.5a1 1 0 0 1-1-1v-2Zm9.5-8.5a1 1 0 0 1 1-1h4.5a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-4.5a1 1 0 0 1-1-1v-2Zm0 5.5a1 1 0 0 1 1-1h4.5a1 1 0 0 1 1 1v4.5a1 1 0 0 1-1 1h-4.5a1 1 0 0 1-1-1v-4.5Z" />',
        'pagos' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M4.5 5.25h15a2.25 2.25 0 0 1 2.25 2.25v9a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25v-9A2.25 2.25 0 0 1 4.5 5.25Zm2 9.5h4" />',
        'pedidos' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5V6a3.75 3.75 0 1 1 7.5 0v1.5m-9.75 0h12l.75 12.75a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5L4.5 7.5Z" />',
        'productos' => '<path stroke-linecap="round" stroke-linejoin="round" d="m3.75 8.25 8.25-4.5 8.25 4.5-8.25 4.5-8.25-4.5Zm0 0v7.5l8.25 4.5m0-12v12m0-12 8.25-4.5m-8.25 16.5 8.25-4.5v-7.5" />',
        'sitio' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-9 3L20.25 3m0 0h-5.5m5.5 0v5.5" />',
    ];
@endphp

<div x-data="{ sidebarAbierto: false, navegando: false }" @click.capture="if ($event.target.closest('a[href]') && !$event.ctrlKey && !$event.metaKey) navegando = true" class="flex h-full">

    {{-- Barra de progreso de navegación (evita sensación de página "congelada" al cambiar de rango/filtro) --}}
    <div x-show="navegando" x-cloak x-transition.opacity class="pointer-events-none fixed inset-x-0 top-0 z-[60] h-0.5 overflow-hidden bg-accent/15">
        <div class="h-full w-1/3 bg-accent" style="animation: enlix-loading-bar 0.9s ease-in-out infinite;"></div>
    </div>

    {{-- Sidebar desktop: expandida desde xl (1280px), colapsada a iconos entre lg y xl (1024-1279px) --}}
    <aside class="hidden lg:flex lg:w-16 xl:w-60 shrink-0 flex-col bg-sidebar px-2 xl:px-4 py-5">
        <div class="flex items-center justify-center gap-2 px-1 pb-6 xl:justify-start xl:px-2">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-accent text-sm font-semibold text-white">e</span>
            <span class="hidden text-sm font-semibold text-white xl:inline">enlix</span>
            <span class="ml-1 hidden rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-medium text-sidebar-item xl:inline">Admin</span>
        </div>

        <nav class="flex flex-1 flex-col gap-1">
            @foreach ($enlacesMenu as $enlace)
                <a
                    href="{{ $enlace['url'] }}"
                    @if ($enlace['activo']) aria-current="page" @endif
                    title="{{ $enlace['label'] }}"
                    class="group relative flex h-11 items-center justify-center gap-3 rounded-nav px-3 text-sm font-medium xl:justify-between {{ $enlace['activo'] ? 'bg-sidebar-active text-white' : 'text-sidebar-item hover:text-white' }}"
                >
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">{!! $iconosMenu[$enlace['icono']] !!}</svg>
                        <span class="hidden xl:inline">{{ $enlace['label'] }}</span>
                    </span>
                    @if (! empty($enlace['badge']))
                        <span class="hidden rounded-full bg-pendiente-dot/90 px-1.5 py-0.5 text-[11px] font-mono font-medium text-white xl:inline">{{ $enlace['badge'] }}</span>
                        <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-pendiente-dot xl:hidden" aria-hidden="true"></span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="mt-auto flex flex-col gap-3 border-t border-white/10 pt-4">
            <a href="{{ route('inicio') }}" title="Ver sitio" class="flex h-11 items-center justify-center gap-3 rounded-nav px-3 text-sm font-medium text-sidebar-item hover:text-white xl:justify-start">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">{!! $iconosMenu['sitio'] !!}</svg>
                <span class="hidden xl:inline">Ver sitio</span>
            </a>
            <div class="flex items-center justify-center gap-2 px-3 xl:justify-start">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-semibold text-white">
                    {{ Illuminate\Support\Str::of(auth()->user()->name ?? 'Admin')->substr(0, 2)->upper() }}
                </span>
                <div class="hidden min-w-0 xl:block">
                    <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name ?? 'Admin' }}</p>
                    <p class="truncate text-xs text-sidebar-item">Administrador</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- Sidebar como drawer por debajo de lg (incluye tablet 768-1023px y móvil) --}}
    <div
        x-show="sidebarAbierto"
        x-cloak
        class="fixed inset-0 z-40 bg-black/40 lg:hidden"
        @click="sidebarAbierto = false"
    ></div>

    <aside
        x-show="sidebarAbierto"
        x-cloak
        x-transition
        class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-sidebar px-4 py-5 lg:hidden"
    >
        <div class="flex items-center justify-between px-2 pb-6">
            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-accent text-sm font-semibold text-white">e</span>
                <span class="text-sm font-semibold text-white">enlix</span>
            </div>
            <button type="button" @click="sidebarAbierto = false" aria-label="Cerrar menú" class="flex h-9 w-9 items-center justify-center rounded-nav text-sidebar-item hover:text-white">
                ✕
            </button>
        </div>

        <nav class="flex flex-1 flex-col gap-1">
            @foreach ($enlacesMenu as $enlace)
                <a
                    href="{{ $enlace['url'] }}"
                    class="flex h-11 items-center gap-3 rounded-nav px-3 text-sm font-medium {{ $enlace['activo'] ? 'bg-sidebar-active text-white' : 'text-sidebar-item hover:text-white' }}"
                >
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">{!! $iconosMenu[$enlace['icono']] !!}</svg>
                    <span class="flex-1">{{ $enlace['label'] }}</span>
                    @if (! empty($enlace['badge']))
                        <span class="rounded-full bg-pendiente-dot/90 px-1.5 py-0.5 text-[11px] font-mono font-medium text-white">{{ $enlace['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('admin.logout') }}" class="mt-auto border-t border-white/10 pt-4">
            @csrf
            <button type="submit" class="flex h-11 w-full items-center rounded-nav px-3 text-left text-sm font-medium text-sidebar-item hover:text-white">
                Cerrar sesión
            </button>
        </form>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        {{-- App bar (móvil + tablet, por debajo de lg) --}}
        <header class="flex h-[60px] shrink-0 items-center justify-between border-b border-border bg-sidebar px-4 lg:hidden">
            <button type="button" @click="sidebarAbierto = true" aria-label="Abrir menú" class="flex h-9 w-9 items-center justify-center rounded-nav text-white">
                <span class="sr-only">Abrir menú</span>
                ☰
            </button>
            <div class="flex items-center gap-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-accent text-xs font-semibold text-white">e</span>
                <span class="text-sm font-semibold text-white">enlix</span>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="text-xs font-medium text-sidebar-item">Salir</button>
            </form>
        </header>

        <main class="flex min-h-0 flex-1 flex-col {{ ($sinScroll ?? false) ? 'overflow-y-auto p-3 sm:p-4 md:overflow-hidden md:p-5 lg:p-6' : 'overflow-y-auto px-4 py-6 md:px-8 md:py-8' }}">
            {{ $slot }}
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
