---
name: laravel-auditoria-rutas
description: Usar antes de modificar cualquier módulo Laravel para mapear rutas, controladores, FormRequests y vistas/JS involucrados.
---

# Auditoría de rutas Laravel

## Pasos
1. Listar rutas del módulo:
   ```bash
   php artisan route:list --path=admin/productos
   php artisan route:list --path=admin/productos --json
   ```
2. Construir la tabla: `ruta → método → nombre → controlador@método → FormRequest → vista/JS que la llama`.
3. Para cada método del controlador, abrir su firma y anotar el FormRequest tipado (`StoreXRequest $request`) o la validación inline (`$request->validate([...])`).
4. Buscar quién consume cada ruta:
   ```bash
   grep -rn "route('admin.productos" resources/ app/
   grep -rn "/admin/productos" resources/js resources/views
   ```

## Checklist
- [ ] Toda ruta tiene nombre (`->name(...)`). Las URLs en JS salen de `route()` en Blade, no hardcodeadas.
- [ ] Todo el grupo tiene middleware `auth` + el de rol admin.
- [ ] Sin rutas duplicadas (mismo método+URI) ni rutas sombreadas: las rutas literales (`/verificar-referencia`) van ANTES de `/{producto}`.
- [ ] Rutas con route-model-binding y SoftDeletes que necesiten eliminados usan `->withTrashed()`.
- [ ] Endpoints de verificación y de guardado usan la misma regla de validación (ver `laravel-validacion-softdeletes`).

## Ejemplo
```php
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('productos/verificar-referencia', [ProductosController::class, 'verificarReferencia'])
        ->name('productos.verificar-referencia');
    Route::post('productos/{producto}/restaurar', [ProductosController::class, 'restaurar'])
        ->withTrashed()->name('productos.restaurar');
    Route::resource('productos', ProductosController::class)->except(['create', 'edit', 'show']);
});
```
