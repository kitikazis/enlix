---
name: laravel-modal-alpine-formularios
description: Usar en modales Alpine.js con pestañas que envían por fetch/XHR a Laravel y deben mostrar errores 422 por campo.
---

# Modales Alpine + Laravel (fetch, 422, pestañas)

## Reglas
- Headers: `X-CSRF-TOKEN` (de `<meta name="csrf-token">`), `Accept: application/json`, `X-Requested-With: XMLHttpRequest`.
- Con archivo: `FormData`; en update `POST` + `_method=PUT`.
- 422 → `errors = json.errors`; mostrar `errors[campo][0]` bajo cada input; saltar a la pestaña del primer campo con error usando un mapa `campo → pestaña`.
- Al escribir en un campo, borrar su error.
- Verificación remota con debounce 400 ms + `AbortController`; limpiar el estado ("disponible"/"en uso") en cuanto cambia el valor y cuando el servidor devuelve 422 en ese campo.
- Éxito: cerrar modal, refrescar lista (recarga parcial o `location.reload()` si la lista es Blade), toast.
- Imagen: obligatoria en create, opcional en update; `accept="image/jpeg,image/png,image/webp"`, validar 2 MB también en cliente.

## Ejemplo
```js
const TAB_DE_CAMPO = { nombre: 'general', referencia: 'general', descripcion: 'descripcion', specs: 'especificaciones' };

async guardar() {
    this.errors = {};
    this.guardando = true;
    const fd = new FormData(this.$refs.form);
    if (this.editId) fd.append('_method', 'PUT');
    try {
        const res = await fetch(this.editId ? `${this.baseUrl}/${this.editId}` : this.baseUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        });
        const data = await res.json().catch(() => ({}));
        if (res.status === 422) {
            this.errors = data.errors || {};
            if (this.errors.referencia) this.refEstado = null;
            const primero = Object.keys(this.errors)[0]?.split('.')[0];
            if (primero && TAB_DE_CAMPO[primero]) this.tab = TAB_DE_CAMPO[primero];
            return;
        }
        if (!res.ok) throw new Error(data.message || 'Error al guardar');
        this.cerrar();
        this.toast(data.message || 'Guardado');
        this.refrescar();
    } catch (e) {
        this.toast(e.message, 'error');
    } finally {
        this.guardando = false;
    }
},

verificarReferencia() {
    this.refEstado = null;
    clearTimeout(this.refTimer);
    this.refAbort?.abort();
    const ref = this.form.referencia.trim();
    if (!ref) return;
    this.refTimer = setTimeout(async () => {
        this.refAbort = new AbortController();
        try {
            const res = await fetch(`${this.verificarUrl}?referencia=${encodeURIComponent(ref)}`, {
                headers: { 'Accept': 'application/json' }, signal: this.refAbort.signal,
            });
            this.refEstado = await res.json();
        } catch (e) {
            if (e.name !== 'AbortError') this.refEstado = null;
        }
    }, 400);
},
```
