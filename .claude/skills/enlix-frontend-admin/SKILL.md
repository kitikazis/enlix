---
name: enlix-frontend-admin
description: Usar al editar vistas Blade o JS inline del panel admin de enlix (Alpine.js, Tailwind con tokens propios, CSP con nonce).
---

# Frontend del admin de enlix

## CSP (app/Http/Middleware/SecurityHeaders.php)
- Todo `<script>` inline lleva nonce; sin él el navegador lo bloquea en silencio y Alpine deja la página "muerta":
  ```blade
  @push('scripts')
      <script nonce="{{ $cspNonce }}">
          function miApp() { return { /* ... */ }; }
      </script>
  @endpush
  ```
- CDN permitido para scripts/estilos: solo `cdn.jsdelivr.net` (cdnjs está bloqueado). Fuentes: `fonts.googleapis.com`.
- `connect-src 'self'`: el `fetch` solo puede ir al propio dominio (más Izipay).
- No agregar librerías nuevas; si hace falta un CDN nuevo, hay que tocar el CSP a propósito.
- Existe un test que verifica el nonce del script de productos; mantenerlo verde.

## Alpine: errores que ya rompieron el admin
- **No usar `this.metodo()` al construir el objeto de `x-data`** (`form: this.formVacio()`): `this` aún no es el componente, lanza TypeError y deja TODA la página sin datos. Usar una función plana fuera del `return {}`.
- **No anidar `x-data`** dentro de un componente que necesita sus métodos (ej. `scrollFade` dentro de `productosApp`): el hijo tapa el scope y `abrirEditar` deja de existir ahí.
- Filas insertadas por JS (`insertarFila(html)`): llamar `window.Alpine.initTree(nodo)`.
- Inicializaciones de librerías externas (Quill) dentro de `try/catch` para que un CDN caído no tumbe el componente.

## Peticiones al backend
```js
headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
}
```
- Archivos: `FormData` + `POST` con `_method=PUT|PATCH`.
- Manejar 422 (errores por campo), 419 (sesión expirada → toast "recarga la página"), 413 (archivo grande).
- Patrones de modal/pestañas/debounce: skill `laravel-modal-alpine-formularios`. Diseño móvil: skill `ui-mobile-first`.

## Tokens de diseño (resources/css/admin.css)
Usar los tokens, no colores de Tailwind sueltos (`red-600`, `green-700`):
| Uso | Clases |
|---|---|
| Fondo página / tarjeta / borde | `bg-page`, `bg-card`, `border-border` |
| Texto | `text-text-primary`, `text-text-secondary`, `text-text-caption` |
| Acción principal | `bg-accent hover:bg-accent-hover`, `text-accent` |
| Error | `text-rechazado-text`, `bg-rechazado-bg`, `border-rechazado-text`, `bg-danger-strong` |
| Éxito | `text-pagado-text`, `bg-pagado-bg` |
| Estados de pedido/pago | `pendiente-*`, `verificacion-*`, `pagado-*`, `rechazado-*`, `anulado-*` (`-bg`, `-text`, `-dot`) |
| Radio | `rounded-nav` (controles), `rounded-2xl` (modales) |

## Componentes Blade existentes
- Layout: `<x-layouts.admin-dashboard :titulo="...">`. Ya pone el contenedor (`max-w-7xl`, `px-4 sm:px-6 lg:px-8`): las páginas NO agregan `mx-auto max-w-*` propio salvo detalles angostos (`max-w-3xl/4xl`). `:sin-scroll="true"` = la página ocupa el alto de la ventana desde lg (Productos); `sin-scroll="xl"` = solo desde xl (Dashboard).
- Shell: topbar + drawer + barra inferior en móvil (<md), rail con tooltips (md–xl), sidebar expandida (xl+). Estado en `adminShell()` (layout).
- `<x-admin.page-header title subtitle :back back-label>` con `<x-slot:actions>`: header de TODA página.
- `<x-admin.data-list :columns caption scroll="y|fill" body-id>`: tabla desde lg, tarjetas debajo. Cada `<td>` lleva `data-label="..."` o `data-dl="title|aside|status|media|actions|mobile"` (ver admin.css).
- Acciones de fila: `.row-actions` + `.row-menu[data-row-menu]` (menú "⋯" / hoja inferior en móvil, en línea desde lg). Delegación global, sin x-data. Ver `productos/_fila.blade.php`.
- `<x-admin.filtros :activos :abierto>`: filtros plegables en móvil (solo páginas sin x-data propio).
- `<x-admin.card title :padded :scroll :fill>`, `<x-admin.empty-state title description icon action-url action-label>`, `<x-admin.kpi label value caption icon tone alerta>`, `<x-admin.badge-estado :estado>`, `<x-admin.badge tone="success|warning|info|danger|neutral|muted|prueba">`, `<x-admin.icon name>`.
- Paginación: `$items->links('admin.partials.paginacion')` (la vista por defecto de Laravel sale sin estilos en admin.css).
- Clases: `.btn` + `.btn-primary|secondary|ghost|danger` + `.btn-sm|lg|icon` (44px en móvil; `aria-busy="true"` = spinner), `.field-label`, `.field-input`, `.skeleton`, `.count-badge`. Son de `@layer components`: NO aceptan variantes (`max-lg:btn-secondary` no existe); para variar por breakpoint usar utilidades (`max-lg:w-full`, `lg:bg-transparent`).
- Datos de prueba: `$pago|$pedido|$producto->esPrueba()` + `<x-admin.badge tone="prueba" :dot="false">Prueba</x-admin.badge>`.

## Textos
- Toda la UI y los mensajes de error en español; los de validación salen de `messages()`/`attributes()` del FormRequest o del `validate()` inline, nunca los de Laravel en inglés (`APP_LOCALE=en`).
