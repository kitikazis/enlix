---
name: laravel-validacion-softdeletes
description: Usar al escribir reglas unique (o endpoints de "verificar disponibilidad") sobre modelos con SoftDeletes.
---

# Validación `unique` con SoftDeletes

## Reglas
1. Decide explícitamente el criterio y aplícalo en TODOS lados:
   - **Única solo entre activos**: `Rule::unique('tabla','col')->whereNull('deleted_at')`. El índice único de BD no puede ser solo `col` (chocaría con eliminados) → índice compuesto o columna generada.
   - **Única incluso entre eliminados** (recomendado si el índice único simple ya existe): `Rule::unique('tabla','col')` sin filtro, y el endpoint de verificación debe consultar `withTrashed()` y ofrecer **restaurar**.
2. En update: `->ignore($modelo->id)`.
3. Endpoint de verificación y FormRequest comparten la regla (método estático en el FormRequest o en el modelo) y la misma normalización (`trim`, mayúsculas) en `prepareForValidation()`.
4. Índice único en BD (migración) para cerrar la race condition; capturar `UniqueConstraintViolationException` y devolver 422.
5. Mensajes en español con `messages()` y `attributes()`.

## Ejemplo
```php
class StoreProductoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['referencia' => Producto::normalizarReferencia($this->input('referencia'))]);
    }

    public function rules(): array
    {
        return [
            'referencia' => ['required', 'string', 'max:50', Rule::unique('productos', 'referencia')->ignore($this->route('producto'))],
        ];
    }

    public function messages(): array
    {
        return ['referencia.unique' => 'Ya existe un producto con esta referencia.'];
    }

    public function attributes(): array
    {
        return ['referencia' => 'referencia'];
    }
}
```

```php
public function verificarReferencia(Request $request)
{
    $ref = Producto::normalizarReferencia($request->query('referencia'));
    $existente = Producto::withTrashed()->where('referencia', $ref)
        ->when($request->query('ignorar'), fn ($q, $id) => $q->whereKeyNot($id))
        ->first();

    return response()->json([
        'disponible' => ! $existente,
        'eliminado'  => (bool) $existente?->trashed(),
        'producto_id' => $existente?->id,
    ]);
}
```

```php
Schema::table('productos', fn (Blueprint $t) => $t->unique('referencia'));
```
