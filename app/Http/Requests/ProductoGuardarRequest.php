<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Producto;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Crear y editar productos desde el modal del admin (store y update).
 *
 * La referencia es única INCLUSO entre productos eliminados (papelera): el
 * índice productos_referencia_unique de la BD es simple, no filtra
 * deleted_at, así que validar con whereNull('deleted_at') dejaría pasar una
 * referencia que MySQL rechaza después con un 500. La regla usa
 * Producto::conReferencia(), la misma consulta que el chequeo en vivo
 * (ProductosController::verificarReferencia), para que ambos nunca
 * vuelvan a dar respuestas distintas.
 */
class ProductoGuardarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('referencia')) {
            $this->merge(['referencia' => Producto::normalizarReferencia($this->input('referencia'))]);
        }
    }

    public function rules(): array
    {
        return [
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'referencia' => ['required', 'string', 'max:50', $this->referenciaUnica()],
            'nombre' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:170', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'descripcion' => ['required', 'string', 'max:20000'],
            'especificaciones' => ['nullable', 'string'],
            // Se escribe en soles; el controller lo pasa a céntimos con bcmath.
            // Mínimo 0.01 y no 0: Izipay no acepta cobros de S/ 0.00.
            'precio' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'activo' => ['nullable', 'boolean'],
            'destacado' => ['nullable', 'boolean'],
            'eliminar_imagen' => ['nullable', 'boolean'],
            'imagen' => [$this->esEdicion() ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'boolean' => 'El campo :attribute no es válido.',
            'max.string' => 'El campo :attribute no puede pasar de :max caracteres.',
            'max.numeric' => 'El campo :attribute no puede ser mayor a :max.',
            'min.numeric' => 'El campo :attribute no puede ser menor a :min.',

            'categoria_id.required' => 'Selecciona una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'slug.regex' => 'El slug solo admite minúsculas, números y guiones.',
            'precio.min' => 'El precio debe ser mayor a S/ 0.00.',
            'precio.max' => 'El precio no puede superar S/ 99,999.99.',
            'stock.min' => 'El stock no puede ser negativo.',
            'orden.min' => 'El orden no puede ser negativo.',
            'imagen.required' => 'Sube una imagen del producto (JPG, PNG o WEBP · máx. 2 MB).',
            'imagen.uploaded' => 'No se pudo subir la imagen. Debe pesar máx. 2 MB.',
            'imagen.image' => 'El archivo debe ser una imagen JPG, PNG o WEBP.',
            'imagen.mimes' => 'Formato no permitido. Usa JPG, PNG o WEBP.',
            'imagen.max' => 'La imagen no puede pesar más de 2 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'categoria_id' => 'categoría',
            'referencia' => 'referencia',
            'nombre' => 'producto',
            'slug' => 'slug',
            'descripcion' => 'descripción',
            'especificaciones' => 'especificaciones',
            'precio' => 'precio',
            'stock' => 'stock',
            'orden' => 'orden',
            'activo' => 'visible',
            'destacado' => 'destacado',
            'imagen' => 'imagen',
        ];
    }

    public function producto(): ?Producto
    {
        $producto = $this->route('producto');

        return $producto instanceof Producto ? $producto : null;
    }

    private function esEdicion(): bool
    {
        return $this->producto()?->exists === true;
    }

    private function referenciaUnica(): Closure
    {
        return function (string $attribute, mixed $valor, Closure $fail): void {
            if (! is_string($valor) || $valor === '') {
                return;
            }

            $existente = Producto::conReferencia($valor, $this->producto()?->id);

            if ($existente === null) {
                return;
            }

            $fail($existente->trashed()
                ? "Esta referencia es de «{$existente->nombre}», que está en la papelera. Restáuralo o usa otra referencia."
                : "Ya existe un producto con esta referencia («{$existente->nombre}»).");
        };
    }
}
