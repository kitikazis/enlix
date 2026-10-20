---
name: ui-mobile-first
description: Usar en cualquier UI nueva o modificada (vistas Blade, modales, formularios) para garantizar diseño mobile-first.
---

# UI mobile-first

## Checklist
- [ ] Estilos base para móvil; `sm:`/`md:`/`lg:` solo amplían.
- [ ] Formularios: una columna en móvil (`grid grid-cols-1 sm:grid-cols-2`).
- [ ] Modal: ancho completo en móvil, `max-h-[90dvh]`, header y footer fijos, cuerpo con `overflow-y-auto`.
- [ ] Botones e inputs táctiles: mínimo 44px de alto (`min-h-[44px]`).
- [ ] Pestañas: `flex overflow-x-auto whitespace-nowrap` sin barra de scroll visible.
- [ ] Sin overflow horizontal: `min-w-0` en hijos flex, `break-words` en textos largos, tablas dentro de `overflow-x-auto`.
- [ ] Inputs numéricos con `inputmode="decimal"`/`"numeric"`.
- [ ] Texto de inputs ≥16px en móvil para evitar zoom en iOS.

## Ejemplo de modal
```html
<div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4">
  <div class="flex w-full sm:max-w-2xl max-h-[90dvh] flex-col rounded-t-2xl sm:rounded-2xl bg-white">
    <header class="shrink-0 border-b px-4 py-3">…</header>
    <nav class="flex shrink-0 gap-2 overflow-x-auto whitespace-nowrap border-b px-4">…</nav>
    <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">…</div>
    </div>
    <footer class="flex shrink-0 flex-col-reverse gap-2 border-t px-4 py-3 sm:flex-row sm:justify-end">
      <button class="min-h-[44px] rounded-lg px-4">Cancelar</button>
      <button class="min-h-[44px] rounded-lg bg-blue-600 px-4 text-white">Guardar</button>
    </footer>
  </div>
</div>
```
