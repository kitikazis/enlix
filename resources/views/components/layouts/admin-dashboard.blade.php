@php
    // Debe calcularse antes del <html> de abajo: el atributo data-modo se usa
    // para recolorear todo el panel (ver admin.css) y necesita el valor
    // disponible desde la primera etiqueta, no desde el <body>.
    $modoPrueba = (bool) session('admin_modo_prueba', false);

    // sinScroll: la página ocupa exactamente el alto de la ventana y sus
    // tarjetas hacen scroll por dentro, solo desde cierto ancho. true = lg
    // (Productos, Papelera); 'xl' = Dashboard (su grilla de 4 filas solo
    // entra cómoda en pantallas grandes). Por debajo, scroll normal.
    $sinScroll = $sinScroll ?? false;
    $claseAjuste = match (true) {
        $sinScroll === 'xl' => 'xl:flex xl:h-full xl:min-h-0 xl:flex-col',
        (bool) $sinScroll => 'lg:flex lg:h-full lg:min-h-0 lg:flex-col',
        default => '',
    };
    $claseMainAjuste = match (true) {
        $sinScroll === 'xl' => 'xl:overflow-hidden',
        (bool) $sinScroll => 'lg:overflow-hidden',
        default => '',
    };

    $usuarioNombre = auth()->user()->name ?? 'Admin';
    $iniciales = Illuminate\Support\Str::of($usuarioNombre)->substr(0, 2)->upper();

    $enlacesMenu = [
        ['url' => route('admin.dashboard'), 'activo' => request()->routeIs('admin.dashboard'), 'label' => 'Dashboard', 'icono' => 'dashboard', 'inferior' => true],
        ['url' => route('admin.pagos'), 'activo' => request()->routeIs('admin.pagos'), 'label' => 'Pagos', 'badge' => $badgeAtencion ?? null, 'badgeLabel' => 'pagos por revisar', 'icono' => 'pagos', 'inferior' => true],
        ['url' => route('admin.pedidos.index'), 'activo' => request()->routeIs('admin.pedidos.*'), 'label' => 'Pedidos', 'icono' => 'pedidos', 'inferior' => true],
        ['url' => route('admin.productos.index'), 'activo' => request()->routeIs('admin.productos.*'), 'label' => 'Productos', 'icono' => 'productos', 'inferior' => true],
        ['url' => route('admin.reclamos.index'), 'activo' => request()->routeIs('admin.reclamos.*'), 'label' => 'Reclamos', 'badge' => $reclamosPendientes ?: null, 'badgeLabel' => 'reclamos pendientes', 'icono' => 'reclamos', 'inferior' => false],
        ['url' => route('admin.paginas-legales.index'), 'activo' => request()->routeIs('admin.paginas-legales.*'), 'label' => 'Legal', 'icono' => 'legal', 'inferior' => false],
    ];

    // "Más" de la barra inferior queda activo si la página actual no está
    // entre los 4 accesos directos (Reclamos, Legal).
    $masActivo = collect($enlacesMenu)->contains(fn ($e) => $e['activo'] && ! $e['inferior']);
    $badgeMas = collect($enlacesMenu)->filter(fn ($e) => ! $e['inferior'])->sum(fn ($e) => (int) ($e['badge'] ?? 0));
@endphp
<!DOCTYPE html>
<html lang="es" class="h-full" data-modo="{{ $modoPrueba ? 'prueba' : 'real' }}" data-tema="claro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titulo ?? 'Dashboard - Enlix Admin' }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/img/favicon.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite('resources/css/admin.css')
    @stack('head')
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
    <script nonce="{{ $cspNonce }}">
        // Tema: se aplica antes de pintar para que no "parpadee" en claro.
        // Por defecto claro; "sistema" sigue prefers-color-scheme (admin.css).
        try {
            if (localStorage.getItem('enlix-admin-tema') === 'sistema') {
                document.documentElement.dataset.tema = 'sistema';
            }
        } catch (e) { /* localStorage bloqueado: queda en claro */ }

        // Fade inferior en listas/tablas con scroll interno propio (ver
        // .dash-scroll-fade en admin.css). Funcion global PLANA a proposito,
        // no un componente Alpine (x-data): si fuera x-data, cualquier
        // elemento interactivo adentro (ej. el boton "Editar" de la tabla
        // de productos) quedaria en un scope anidado nuevo, y @click ahi
        // dejaba de encontrar los metodos del x-data raiz de la pagina.
        // Esto solo usa $el (magic de Alpine, no depende de ningun scope) y
        // una funcion de window comun, asi que no crea ningun scope nuevo.
        function actualizarScrollFade(el) {
            el.classList.toggle('dash-scroll-fade', el.scrollHeight - el.scrollTop - el.clientHeight > 4);
        }

        // Menú "⋯" de acciones de fila (ver .row-menu en admin.css). Mismo
        // criterio que actualizarScrollFade: delegación global, sin x-data,
        // así funciona igual en filas insertadas por JS y no tapa el scope
        // de productosApp()/papeleraApp().
        function cerrarMenusFila(devolverFoco) {
            document.querySelectorAll('[data-row-menu][data-abierto]').forEach((menu) => {
                menu.removeAttribute('data-abierto');
                const boton = menu.querySelector('.row-menu-toggle');
                if (boton) {
                    boton.setAttribute('aria-expanded', 'false');
                    if (devolverFoco) {
                        boton.focus();
                    }
                }
            });
        }

        document.addEventListener('click', (evento) => {
            const boton = evento.target.closest('.row-menu-toggle');
            if (boton) {
                const menu = boton.closest('[data-row-menu]');
                const abrir = !menu.hasAttribute('data-abierto');
                cerrarMenusFila(false);
                if (abrir) {
                    menu.setAttribute('data-abierto', '');
                    boton.setAttribute('aria-expanded', 'true');
                    menu.querySelector('.row-menu-panel .btn:not([disabled])')?.focus();
                }
                return;
            }
            // Click en una acción, en el fondo o fuera: se cierra. Los
            // @click de Alpine de la acción ya corrieron (burbujeo).
            if (!evento.target.closest('.row-menu-panel') || evento.target.closest('.row-menu-panel .btn')) {
                cerrarMenusFila(false);
            }
        });

        document.addEventListener('keydown', (evento) => {
            if (evento.key === 'Escape' && document.querySelector('[data-row-menu][data-abierto]')) {
                cerrarMenusFila(true);
            }
        });

        // Shell del panel: drawer móvil, menú de cuenta, barra de carga y
        // tema. Solo estado de interfaz, nada de datos.
        function adminShell() {
            const seleccionEnfocables = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

            return {
                drawerAbierto: false,
                cuentaAbierta: false,
                navegando: false,
                modoModalAbierto: false,
                temaSistema: document.documentElement.dataset.tema === 'sistema',

                abrirDrawer() {
                    this.drawerAbierto = true;
                    this.$nextTick(() => {
                        this.$refs.drawer.querySelector(seleccionEnfocables)?.focus();
                    });
                },

                cerrarDrawer(devolverFoco = true) {
                    if (!this.drawerAbierto) {
                        return;
                    }
                    this.drawerAbierto = false;
                    if (devolverFoco) {
                        this.$nextTick(() => this.$refs.botonMenu?.focus());
                    }
                },

                // Trap de foco: Tab / Shift+Tab no salen del drawer abierto.
                atraparFoco(evento) {
                    const enfocables = [...this.$refs.drawer.querySelectorAll(seleccionEnfocables)].filter((el) => el.offsetParent !== null);
                    if (!enfocables.length) {
                        return;
                    }
                    const primero = enfocables[0];
                    const ultimo = enfocables[enfocables.length - 1];
                    if (evento.shiftKey && document.activeElement === primero) {
                        evento.preventDefault();
                        ultimo.focus();
                    } else if (!evento.shiftKey && document.activeElement === ultimo) {
                        evento.preventDefault();
                        primero.focus();
                    }
                },

                cerrarConEscape() {
                    if (this.modoModalAbierto) {
                        this.modoModalAbierto = false;
                    } else if (this.drawerAbierto) {
                        this.cerrarDrawer();
                    } else if (this.cuentaAbierta) {
                        this.cuentaAbierta = false;
                    }
                },

                // Navegación por enlace: barra de progreso + cerrar drawer.
                // Se ignoran descargas, pestañas nuevas y anclas internas
                // (si no, la barra quedaba girando para siempre).
                alNavegar(evento) {
                    const enlace = evento.target.closest('a[href]');
                    if (!enlace || evento.ctrlKey || evento.metaKey || evento.shiftKey || evento.button === 1) {
                        return;
                    }
                    if (enlace.target === '_blank' || enlace.hasAttribute('download') || enlace.getAttribute('href').startsWith('#')) {
                        return;
                    }
                    this.navegando = true;
                    this.drawerAbierto = false;
                    this.cuentaAbierta = false;
                },

                alternarTema() {
                    this.temaSistema = !this.temaSistema;
                    const valor = this.temaSistema ? 'sistema' : 'claro';
                    document.documentElement.dataset.tema = valor;
                    try {
                        localStorage.setItem('enlix-admin-tema', valor);
                    } catch (e) { /* sin localStorage: solo esta visita */ }
                },
            };
        }
    </script>
</head>
<body class="h-full bg-page font-sans text-text-primary antialiased">

<a href="#contenido" class="skip-link">Saltar al contenido</a>

<div
    x-data="adminShell()"
    @click.capture="alNavegar($event)"
    @keydown.escape.window="cerrarConEscape()"
    @pageshow.window="navegando = false"
    class="app-shell flex flex-col"
>

    {{-- Barra de progreso de navegación (evita sensación de página "congelada" al cambiar de rango/filtro) --}}
    <div x-show="navegando" x-cloak x-transition.opacity class="pointer-events-none fixed inset-x-0 top-0 z-[80] h-0.5 overflow-hidden bg-accent/15" role="progressbar" aria-label="Cargando">
        <div class="h-full w-1/3 bg-accent" style="animation: enlix-loading-bar 0.9s ease-in-out infinite;"></div>
    </div>

    {{-- Aviso de modo de prueba: visible en todas las pantallas del admin, no solo el dashboard --}}
    @if ($modoPrueba)
        <div class="flex shrink-0 items-center justify-center gap-x-2 bg-pendiente-dot px-4 text-center text-xs font-semibold text-white" role="status">
            <span aria-hidden="true">●</span>
            <span class="py-2">Modo de prueba activo: los datos incluyen pagos ficticios, no reales.</span>
            <button type="button" @click="modoModalAbierto = true" class="inline-flex min-h-touch shrink-0 items-center px-2 underline underline-offset-2 hover:no-underline md:min-h-9">Cambiar</button>
        </div>
    @endif

    <div class="flex min-h-0 flex-1">

        {{-- Sidebar: rail de iconos con tooltip entre md y xl (768-1279px), expandida desde xl --}}
        <aside class="relative z-30 hidden w-[4.5rem] shrink-0 flex-col bg-sidebar px-3 py-4 md:flex xl:w-64 xl:px-4 xl:py-5" aria-label="Barra lateral">
            <div class="flex items-center justify-center gap-2 pb-5 xl:justify-start xl:px-2">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-accent text-sm font-semibold text-on-accent" aria-hidden="true">e</span>
                <span class="hidden text-sm font-semibold text-white xl:inline">enlix</span>
                <span class="ml-1 hidden rounded-full bg-white/10 px-2 py-0.5 text-xs font-medium text-sidebar-item xl:inline">Admin</span>
                <span class="sr-only xl:hidden">enlix Admin</span>
            </div>

            <nav class="flex flex-1 flex-col gap-1" aria-label="Principal">
                @foreach ($enlacesMenu as $enlace)
                    <a
                        href="{{ $enlace['url'] }}"
                        @if ($enlace['activo']) aria-current="page" @endif
                        class="group relative flex h-11 items-center justify-center gap-3 rounded-nav px-3 text-sm font-medium transition-colors xl:justify-start {{ $enlace['activo'] ? 'bg-sidebar-active text-white' : 'text-sidebar-item hover:bg-white/5 hover:text-white' }}"
                    >
                        @if ($enlace['activo'])
                            <span class="absolute inset-y-2 -left-3 w-1 rounded-r-full bg-accent xl:-left-4" aria-hidden="true"></span>
                        @endif
                        <x-admin.icon :name="$enlace['icono']" />
                        <span class="sr-only xl:not-sr-only xl:flex-1">{{ $enlace['label'] }}</span>
                        @if (! empty($enlace['badge']))
                            <span class="hidden min-w-6 rounded-full bg-pendiente-dot px-1.5 text-center text-xs font-semibold leading-5 tabular-nums text-white xl:inline-block" aria-hidden="true">{{ $enlace['badge'] }}</span>
                            <span class="absolute right-1.5 top-1.5 h-2.5 w-2.5 rounded-full bg-pendiente-dot ring-2 ring-sidebar xl:hidden" aria-hidden="true"></span>
                            <span class="sr-only">({{ $enlace['badge'] }} {{ $enlace['badgeLabel'] }})</span>
                        @endif
                        <span class="rail-tooltip" aria-hidden="true">{{ $enlace['label'] }}{{ ! empty($enlace['badge']) ? ' · '.$enlace['badge'] : '' }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="mt-4 flex flex-col gap-1 border-t border-white/10 pt-4">
                <button
                    type="button"
                    @click="modoModalAbierto = true"
                    class="group relative flex h-11 items-center justify-center gap-3 rounded-nav px-3 text-sm font-medium text-sidebar-item transition-colors hover:bg-white/5 hover:text-white xl:justify-start"
                >
                    <x-admin.icon name="modo" />
                    <span class="sr-only xl:not-sr-only xl:flex-1 xl:text-left">Modo: {{ $modoPrueba ? 'Prueba' : 'Real' }}</span>
                    <span class="absolute right-2 top-2 h-2 w-2 rounded-full xl:static xl:h-2 xl:w-2 {{ $modoPrueba ? 'bg-pendiente-dot' : 'bg-pagado-dot' }}" aria-hidden="true"></span>
                    <span class="rail-tooltip" aria-hidden="true">Modo: {{ $modoPrueba ? 'Prueba' : 'Real' }}</span>
                </button>
                <button
                    type="button"
                    @click="alternarTema()"
                    :aria-pressed="temaSistema ? 'true' : 'false'"
                    class="group relative flex h-11 items-center justify-center gap-3 rounded-nav px-3 text-sm font-medium text-sidebar-item transition-colors hover:bg-white/5 hover:text-white xl:justify-start"
                >
                    <x-admin.icon name="tema" />
                    <span class="sr-only xl:not-sr-only xl:flex-1 xl:text-left" x-text="temaSistema ? 'Tema: sistema' : 'Tema: claro'">Tema: claro</span>
                    <span class="rail-tooltip" aria-hidden="true" x-text="temaSistema ? 'Tema: sistema (oscuro si tu equipo lo está)' : 'Tema: claro'">Tema: claro</span>
                </button>
                <a href="{{ route('inicio') }}" target="_blank" rel="noopener" class="group relative flex h-11 items-center justify-center gap-3 rounded-nav px-3 text-sm font-medium text-sidebar-item transition-colors hover:bg-white/5 hover:text-white xl:justify-start">
                    <x-admin.icon name="sitio" />
                    <span class="sr-only xl:not-sr-only">Ver sitio</span>
                    <span class="rail-tooltip" aria-hidden="true">Ver sitio</span>
                </a>

                <div class="mt-2 flex flex-col items-center gap-1 rounded-nav border-t border-white/10 pt-3 xl:flex-row xl:gap-2 xl:px-2">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-semibold text-white" title="{{ $usuarioNombre }}">{{ $iniciales }}</span>
                    <div class="hidden min-w-0 flex-1 xl:block">
                        <p class="truncate text-sm font-medium text-white">{{ $usuarioNombre }}</p>
                        <p class="truncate text-xs text-sidebar-item">Administrador</p>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="group relative flex h-11 w-11 items-center justify-center rounded-nav text-sidebar-item transition-colors hover:bg-white/5 hover:text-white xl:h-9 xl:w-9" aria-label="Cerrar sesión">
                            <x-admin.icon name="salir" />
                            <span class="rail-tooltip" aria-hidden="true">Cerrar sesión</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Topbar móvil (por debajo de md): menú, logo y cuenta --}}
            <header class="relative z-30 flex h-topbar shrink-0 items-center justify-between gap-2 bg-sidebar px-2 md:hidden">
                <button
                    type="button"
                    x-ref="botonMenu"
                    @click="abrirDrawer()"
                    :aria-expanded="drawerAbierto ? 'true' : 'false'"
                    aria-expanded="false"
                    aria-controls="drawer-menu"
                    aria-label="Abrir menú"
                    class="flex h-11 w-11 items-center justify-center rounded-nav text-white hover:bg-white/10"
                >
                    <x-admin.icon name="menu" class="h-6 w-6" />
                </button>

                <a href="{{ route('admin.dashboard') }}" class="flex min-h-touch items-center gap-2 px-2" aria-label="enlix Admin, ir al dashboard">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-accent text-xs font-semibold text-on-accent" aria-hidden="true">e</span>
                    <span class="text-sm font-semibold text-white">enlix</span>
                    @if ($modoPrueba)
                        <span class="rounded-full bg-pendiente-dot px-1.5 text-xs font-semibold text-white">Prueba</span>
                    @endif
                </a>

                <div class="relative" @click.outside="cuentaAbierta = false">
                    <button
                        type="button"
                        @click="cuentaAbierta = !cuentaAbierta"
                        :aria-expanded="cuentaAbierta ? 'true' : 'false'"
                        aria-expanded="false"
                        aria-haspopup="true"
                        aria-label="Cuenta de {{ $usuarioNombre }}"
                        class="flex h-11 w-11 items-center justify-center rounded-nav hover:bg-white/10"
                    >
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15 text-xs font-semibold text-white">{{ $iniciales }}</span>
                    </button>
                    <div
                        x-show="cuentaAbierta"
                        x-cloak
                        x-transition.origin.top.right
                        class="absolute right-0 top-full mt-1 w-60 overflow-hidden rounded-card border border-border bg-card p-1.5 shadow-pop"
                    >
                        <div class="border-b border-border px-3 pb-2.5 pt-2">
                            <p class="truncate text-sm font-semibold text-text-primary">{{ $usuarioNombre }}</p>
                            <p class="text-xs text-text-secondary">Administrador</p>
                        </div>
                        <a href="{{ route('inicio') }}" target="_blank" rel="noopener" class="btn btn-ghost mt-1 w-full justify-start">
                            <x-admin.icon name="sitio" class="h-4 w-4" /> Ver sitio
                        </a>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost w-full justify-start text-rechazado-text">
                                <x-admin.icon name="salir" class="h-4 w-4" /> Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main
                id="contenido"
                tabindex="-1"
                class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain focus:outline-none {{ $claseMainAjuste }}"
                :class="drawerAbierto && 'overflow-hidden!'"
            >
                <div class="mx-auto w-full min-w-0 max-w-7xl px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8 {{ $claseAjuste }}">
                    {{ $slot }}
                </div>
            </main>

            {{-- Barra inferior (móvil): 4 accesos directos + "Más" (abre el drawer) --}}
            <nav class="relative z-30 shrink-0 border-t border-border bg-card pb-[env(safe-area-inset-bottom)] md:hidden" aria-label="Accesos rápidos">
                <ul class="grid h-bottomnav grid-cols-5">
                    @foreach (collect($enlacesMenu)->where('inferior', true) as $enlace)
                        <li>
                            <a
                                href="{{ $enlace['url'] }}"
                                @if ($enlace['activo']) aria-current="page" @endif
                                class="relative flex h-full flex-col items-center justify-center gap-1 text-xs font-medium {{ $enlace['activo'] ? 'text-accent' : 'text-text-secondary' }}"
                            >
                                @if ($enlace['activo'])
                                    <span class="absolute inset-x-4 top-0 h-0.5 rounded-b-full bg-accent" aria-hidden="true"></span>
                                @endif
                                <span class="relative">
                                    <x-admin.icon :name="$enlace['icono']" class="h-6 w-6" />
                                    @if (! empty($enlace['badge']))
                                        <span class="absolute -right-2 -top-1.5 min-w-5 rounded-full bg-pendiente-dot px-1 text-center text-[0.6875rem] font-semibold leading-[1.125rem] tabular-nums text-white ring-2 ring-card" aria-hidden="true">{{ $enlace['badge'] }}</span>
                                    @endif
                                </span>
                                {{ $enlace['label'] }}
                                @if (! empty($enlace['badge']))
                                    <span class="sr-only">({{ $enlace['badge'] }} {{ $enlace['badgeLabel'] }})</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                    <li>
                        <button
                            type="button"
                            @click="abrirDrawer()"
                            :aria-expanded="drawerAbierto ? 'true' : 'false'"
                            aria-expanded="false"
                            aria-controls="drawer-menu"
                            class="relative flex h-full w-full flex-col items-center justify-center gap-1 text-xs font-medium {{ $masActivo ? 'text-accent' : 'text-text-secondary' }}"
                        >
                            @if ($masActivo)
                                <span class="absolute inset-x-4 top-0 h-0.5 rounded-b-full bg-accent" aria-hidden="true"></span>
                            @endif
                            <span class="relative">
                                <x-admin.icon name="mas" class="h-6 w-6" />
                                @if ($badgeMas > 0)
                                    <span class="absolute -right-1 -top-0.5 h-2.5 w-2.5 rounded-full bg-pendiente-dot ring-2 ring-card" aria-hidden="true"></span>
                                @endif
                            </span>
                            Más
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    {{-- Drawer móvil: overlay + panel. Esc, click fuera, navegar o el ✕ lo cierran; el foco queda atrapado adentro mientras está abierto. --}}
    <div class="pointer-events-none fixed inset-0 z-50 md:hidden">
        <div x-show="drawerAbierto" x-cloak x-transition.opacity.duration.200ms class="pointer-events-auto absolute inset-0 bg-black/50" @click="cerrarDrawer()" aria-hidden="true"></div>

        <div
            id="drawer-menu"
            x-ref="drawer"
            x-show="drawerAbierto"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            @keydown.tab="atraparFoco($event)"
            role="dialog"
            aria-modal="true"
            aria-label="Menú"
            class="pointer-events-auto absolute inset-y-0 left-0 flex w-[min(20rem,86vw)] flex-col overflow-y-auto bg-sidebar px-4 pb-[max(1.25rem,env(safe-area-inset-bottom))] pt-4"
        >
            <div class="flex items-center justify-between pb-5 pl-2">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-accent text-sm font-semibold text-on-accent" aria-hidden="true">e</span>
                    <span class="text-sm font-semibold text-white">enlix</span>
                    <span class="rounded-full bg-white/10 px-2 py-0.5 text-xs font-medium text-sidebar-item">Admin</span>
                </div>
                <button type="button" @click="cerrarDrawer()" aria-label="Cerrar menú" class="flex h-11 w-11 items-center justify-center rounded-nav text-sidebar-item hover:bg-white/10 hover:text-white">
                    <x-admin.icon name="cerrar" class="h-6 w-6" />
                </button>
            </div>

            <nav class="flex flex-col gap-1" aria-label="Principal (móvil)">
                @foreach ($enlacesMenu as $enlace)
                    <a
                        href="{{ $enlace['url'] }}"
                        @if ($enlace['activo']) aria-current="page" @endif
                        class="relative flex h-12 items-center gap-3 rounded-nav px-3 text-[0.9375rem] font-medium {{ $enlace['activo'] ? 'bg-sidebar-active text-white' : 'text-sidebar-item hover:bg-white/5 hover:text-white' }}"
                    >
                        @if ($enlace['activo'])
                            <span class="absolute inset-y-2.5 -left-4 w-1 rounded-r-full bg-accent" aria-hidden="true"></span>
                        @endif
                        <x-admin.icon :name="$enlace['icono']" />
                        <span class="flex-1">{{ $enlace['label'] }}</span>
                        @if (! empty($enlace['badge']))
                            <span class="min-w-6 rounded-full bg-pendiente-dot px-1.5 text-center text-xs font-semibold leading-5 tabular-nums text-white">{{ $enlace['badge'] }}<span class="sr-only"> {{ $enlace['badgeLabel'] }}</span></span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="mt-auto flex flex-col gap-1 border-t border-white/10 pt-4">
                <button
                    type="button"
                    @click="cerrarDrawer(false); modoModalAbierto = true"
                    class="flex h-12 items-center gap-3 rounded-nav px-3 text-left text-[0.9375rem] font-medium text-sidebar-item hover:bg-white/5 hover:text-white"
                >
                    <x-admin.icon name="modo" />
                    <span class="flex-1">Modo: {{ $modoPrueba ? 'Prueba' : 'Real' }}</span>
                    <span class="h-2 w-2 shrink-0 rounded-full {{ $modoPrueba ? 'bg-pendiente-dot' : 'bg-pagado-dot' }}" aria-hidden="true"></span>
                </button>
                <button
                    type="button"
                    @click="alternarTema()"
                    :aria-pressed="temaSistema ? 'true' : 'false'"
                    class="flex h-12 items-center gap-3 rounded-nav px-3 text-left text-[0.9375rem] font-medium text-sidebar-item hover:bg-white/5 hover:text-white"
                >
                    <x-admin.icon name="tema" />
                    <span class="flex-1" x-text="temaSistema ? 'Tema: sistema' : 'Tema: claro'">Tema: claro</span>
                </button>
                <a href="{{ route('inicio') }}" target="_blank" rel="noopener" class="flex h-12 items-center gap-3 rounded-nav px-3 text-[0.9375rem] font-medium text-sidebar-item hover:bg-white/5 hover:text-white">
                    <x-admin.icon name="sitio" />
                    Ver sitio
                </a>

                <div class="mt-2 flex items-center gap-3 border-t border-white/10 px-1 pt-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-semibold text-white" aria-hidden="true">{{ $iniciales }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ $usuarioNombre }}</p>
                        <p class="truncate text-xs text-sidebar-item">Administrador</p>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="flex h-11 items-center gap-2 rounded-nav px-3 text-sm font-medium text-sidebar-item hover:bg-white/5 hover:text-white">
                            <x-admin.icon name="salir" class="h-4 w-4" /> Salir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: modo del panel (real/prueba). Cada opcion es su propio POST
         (igual que "Cerrar sesión" arriba): recarga la pagina entera, asi el
         data-modo del <html> y todo lo que depende de $excluirPrueba quedan
         en sync sin necesitar JS adicional. --}}
    <div x-show="modoModalAbierto" x-cloak class="fixed inset-0 z-[60] flex items-end justify-center sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="titulo-modo-panel">
        <div x-show="modoModalAbierto" x-transition.opacity class="absolute inset-0 bg-black/50" @click="modoModalAbierto = false" aria-hidden="true"></div>
        <div x-show="modoModalAbierto" x-transition class="relative w-full rounded-t-2xl bg-card p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] shadow-pop sm:max-w-sm sm:rounded-2xl sm:pb-5">
            <h3 id="titulo-modo-panel" class="mb-1 text-base font-semibold text-text-primary">Modo del panel</h3>
            <p class="mb-4 text-sm text-text-secondary">
                Elige qué datos quieres ver. El modo se recuerda en esta sesión y cambia el color de todo el panel para que no confundas pruebas con datos reales.
            </p>

            <form method="POST" action="{{ route('admin.modo.actualizar') }}" class="mb-3">
                @csrf
                <input type="hidden" name="modo" value="real">
                <button type="submit" class="flex min-h-touch w-full items-start gap-3 rounded-nav border p-3 text-left transition-colors {{ ! $modoPrueba ? 'border-accent bg-accent/10' : 'border-border hover:bg-page' }}">
                    <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-pagado-dot" aria-hidden="true"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-text-primary">Modo real</span>
                        <span class="block text-xs text-text-secondary">Solo pagos y métricas reales. Recomendado para el uso diario.</span>
                    </span>
                    @if (! $modoPrueba)
                        <span class="shrink-0 self-center text-xs font-semibold text-accent">Activo</span>
                    @endif
                </button>
            </form>

            <form method="POST" action="{{ route('admin.modo.actualizar') }}" class="mb-4">
                @csrf
                <input type="hidden" name="modo" value="prueba">
                <button type="submit" class="flex min-h-touch w-full items-start gap-3 rounded-nav border p-3 text-left transition-colors {{ $modoPrueba ? 'border-accent bg-accent/10' : 'border-border hover:bg-page' }}">
                    <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-pendiente-dot" aria-hidden="true"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-text-primary">Modo de prueba</span>
                        <span class="block text-xs text-text-secondary">Incluye pagos de prueba (@example.com o montos ≤ S/ 1.00) en todas las vistas y métricas. Útil para certificar el checkout.</span>
                    </span>
                    @if ($modoPrueba)
                        <span class="shrink-0 self-center text-xs font-semibold text-accent">Activo</span>
                    @endif
                </button>
            </form>

            <div class="flex justify-end">
                <button type="button" @click="modoModalAbierto = false" class="btn btn-secondary w-full sm:w-auto">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@stack('scripts')
</body>
</html>
