<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo ?? 'Dashboard - Enlix Admin' }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/img/favicon.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite('resources/css/admin.css')
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
</head>
<body class="h-full bg-page font-sans text-text-primary antialiased">

@php
    $enlacesMenu = [
        ['url' => route('admin.dashboard'), 'activo' => request()->routeIs('admin.dashboard'), 'label' => 'Dashboard'],
        ['url' => route('admin.dashboard').'#pagos', 'activo' => false, 'label' => 'Pagos', 'badge' => $badgeAtencion ?? null],
        ['url' => route('admin.productos.index'), 'activo' => request()->routeIs('admin.productos.*'), 'label' => 'Productos'],
    ];
@endphp

<div x-data="{ sidebarAbierto: false }" class="flex h-full">

    {{-- Sidebar desktop --}}
    <aside class="hidden md:flex w-60 shrink-0 flex-col bg-sidebar px-4 py-5">
        <div class="flex items-center gap-2 px-2 pb-6">
            <span class="flex h-7 w-7 items-center justify-center rounded-md bg-accent text-sm font-semibold text-white">e</span>
            <span class="text-sm font-semibold text-white">enlix</span>
            <span class="ml-1 rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-medium text-sidebar-item">Admin</span>
        </div>

        <nav class="flex flex-1 flex-col gap-1">
            @foreach ($enlacesMenu as $enlace)
                <a
                    href="{{ $enlace['url'] }}"
                    @if ($enlace['activo']) aria-current="page" @endif
                    class="flex h-11 items-center justify-between rounded-nav px-3 text-sm font-medium {{ $enlace['activo'] ? 'bg-sidebar-active text-white' : 'text-sidebar-item hover:text-white' }}"
                >
                    <span>{{ $enlace['label'] }}</span>
                    @if (! empty($enlace['badge']))
                        <span class="rounded-full bg-pendiente-dot/90 px-1.5 py-0.5 text-[11px] font-mono font-medium text-white">{{ $enlace['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="mt-auto flex flex-col gap-3 border-t border-white/10 pt-4">
            <a href="{{ route('inicio') }}" class="flex h-11 items-center rounded-nav px-3 text-sm font-medium text-sidebar-item hover:text-white">
                Ver sitio
            </a>
            <div class="flex items-center gap-2 px-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-semibold text-white">
                    {{ Illuminate\Support\Str::of(auth()->user()->name ?? 'Admin')->substr(0, 2)->upper() }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name ?? 'Admin' }}</p>
                    <p class="truncate text-xs text-sidebar-item">Administrador</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- Sidebar móvil (offcanvas simple con Alpine) --}}
    <div
        x-show="sidebarAbierto"
        x-cloak
        class="fixed inset-0 z-40 bg-black/40 md:hidden"
        @click="sidebarAbierto = false"
    ></div>

    <aside
        x-show="sidebarAbierto"
        x-cloak
        x-transition
        class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-sidebar px-4 py-5 md:hidden"
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
                    class="flex h-11 items-center justify-between rounded-nav px-3 text-sm font-medium {{ $enlace['activo'] ? 'bg-sidebar-active text-white' : 'text-sidebar-item hover:text-white' }}"
                >
                    <span>{{ $enlace['label'] }}</span>
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
        {{-- App bar móvil --}}
        <header class="flex h-[60px] shrink-0 items-center justify-between border-b border-border bg-sidebar px-4 md:hidden">
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

        <main class="flex-1 overflow-y-auto px-4 py-6 md:px-8 md:py-8">
            {{ $slot }}
        </main>
    </div>
</div>

</body>
</html>
