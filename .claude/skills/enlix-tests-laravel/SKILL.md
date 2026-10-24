---
name: enlix-tests-laravel
description: Usar al escribir o correr tests de enlix (PHPUnit Feature tests) - convenciones, helpers y patrones para admin, uploads y validación 422.
---

# Tests en enlix

## Correr
```bash
php artisan test                                  # suite completa (~240 tests, ~60 s)
php artisan test --filter=AdminProductosTest      # un archivo/clase
php artisan test --filter=test_nombre_del_caso    # un caso
```
- sqlite en memoria (`phpunit.xml`): aislado de producción.
- Las migraciones siembran 3 productos (`plan-web-basico`, `plan-web-profesional`, `plan-web-empresarial`); se pueden usar como fixtures.
- Si un test de otro módulo falla una vez y luego pasa, es intermitente (rate limit/tiempo): reintentar antes de investigar.

## Convenciones
- Clases PHPUnit (no Pest), `use RefreshDatabase;`, nombres `test_` en español con guiones bajos que describen el comportamiento: `test_no_permite_dos_productos_con_la_misma_referencia`.
- Un test de regresión por cada bug arreglado, en el archivo del módulo (`tests/Feature/Admin<Modulo>Test.php`).
- Admin: `User::factory()->create()` + `actingAs($user)`.

## Patrones
```php
// Endpoint JSON del modal (XHR con Accept: application/json)
$this->actingAs($user)
    ->post(route('admin.productos.store'), $this->payload(['referencia' => 'DUP-001']), ['Accept' => 'application/json'])
    ->assertUnprocessable()
    ->assertJsonValidationErrors(['referencia' => 'Ya existe']); // valida también el texto en español

// Update con archivo: POST + _method=PUT (igual que el XHR real)
$this->actingAs($user)->post(
    route('admin.productos.update', $producto),
    array_merge($payload, ['_method' => 'PUT']),
    ['Accept' => 'application/json']
)->assertOk();

// Uploads
Storage::fake('public');
UploadedFile::fake()->image('foto.jpg', 2000, 1500);        // imagen válida
UploadedFile::fake()->create('virus.exe', 100);              // no imagen
Storage::disk('public')->assertExists($producto->imagen);

// Soft deletes
$producto->delete();
$this->assertSoftDeleted($producto);
$this->assertNull($producto->fresh()->deleted_at);           // tras restaurar

// Redirect (formularios clásicos) vs JSON (modal)
$this->patch(route('admin.productos.restaurar', $p))->assertRedirect(route('admin.productos.papelera'));
$this->patchJson(route('admin.productos.restaurar', $p))->assertOk()->assertJsonStructure(['mensaje', 'html']);
```

## Helper de payload
Cada archivo de test de un modal define un `payload(array $overrides = [])` con todos los campos válidos; los tests solo pisan lo que les importa. Reutilizarlo en vez de armar arrays a mano.

## Checklist antes de entregar
- [ ] `php artisan test` completo en verde.
- [ ] Los tests temporales de diagnóstico fueron borrados (`git status` limpio en `tests/` salvo los permanentes).
