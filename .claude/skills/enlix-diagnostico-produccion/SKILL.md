---
name: enlix-diagnostico-produccion
description: Usar cuando un bug se reporta en enlix.pe (producción) antes de tocar código - para confirmar qué versión corre allá y reproducirlo sin riesgo.
---

# Diagnosticar bugs reportados en producción

## Reglas de seguridad
- El `.env` local apunta a la BD de **producción** (ver `DB_HOST` y `APP_ENV` en `.env`).
- Prohibido sin confirmación explícita: `migrate`, `db:seed`, `tinker` con escrituras, `queue:work`, cualquier comando que mande correos o cobre en Izipay.
- Lecturas (`SELECT`) están permitidas, pero la BD de producción suele **no responder desde fuera** (timeout). No insistir: reproducir en sqlite.
- `php artisan test` es seguro: `phpunit.xml` fuerza `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`.

## Paso 1: ¿qué código corre en producción?
Antes de buscar el bug en el código local, confirmar que el servidor corre ese código.
```bash
git status --short           # cambios locales sin commit = no están en producción
git log origin/main --oneline -1
grep -rn "<texto exacto del error>" app resources lang
```
Si el texto del error reportado **no existe** en el código local (ej. mensajes de Laravel en inglés cuando el FormRequest tiene `messages()` en español), el servidor corre una versión vieja: el arreglo es desplegar (skill `enlix-deploy-produccion`), no editar.

## Paso 2: reproducir sin tocar producción
Test temporal en `tests/Feature/`, ejecutarlo y **borrarlo** al terminar:
```php
<?php
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DiagTemporalTest extends TestCase
{
    use RefreshDatabase;

    public function test_reproduce(): void
    {
        // DB::table()->insert() salta mutators: simula datos guardados por código viejo.
        DB::table('productos')->insert([
            'slug' => 'viejo', 'nombre' => 'Viejo', 'descripcion' => 'x', 'precio_centimos' => 1000,
            'referencia' => 'xd', 'activo' => 0, 'deleted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $r = $this->actingAs(User::factory()->create())
            ->getJson(route('admin.productos.verificar-referencia', ['referencia' => 'xd']));

        fwrite(STDOUT, "\n{$r->status()} {$r->getContent()}\n");
        $this->assertTrue(true);
    }
}
```
```bash
php artisan test tests/Feature/DiagTemporalTest.php
rm tests/Feature/DiagTemporalTest.php
```

## Diferencias sqlite vs MySQL a tener en cuenta
- MySQL (`utf8mb4_unicode_ci`) compara sin distinguir mayúsculas/tildes; sqlite sí distingue. Un `unique` que choca en producción puede pasar en sqlite: normalizar (`trim` + `mb_strtoupper`) en el modelo.
- `SoftDeletes`: Eloquent excluye `deleted_at`, pero `Rule::unique`/`unique:` y los índices únicos de BD **no**. Ver skill `laravel-validacion-softdeletes`.

## Entrega del diagnóstico
1. Causa raíz en una frase.
2. Evidencia: salida literal de los comandos/tests.
3. Si ya está arreglado localmente: decirlo y pasar a despliegue, sin reescribir código.
4. Test de regresión permanente en `tests/Feature/` para el caso.
