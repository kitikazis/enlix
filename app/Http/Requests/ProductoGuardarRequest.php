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

    public const MAX_FILAS_SPECS = 50;

    public const MAX_CLAVE_SPEC = 100;

    public const MAX_VALOR_SPEC = 500;

    public const MAX_BENEFICIOS_OFERTA = 3;

    public const MAX_TITULO_BENEFICIO = 60;

    public const MAX_TEXTO_BENEFICIO = 200;

    protected function prepareForValidation(): void
    {
        if ($this->has('referencia')) {
            $this->merge(['referencia' => Producto::normalizarReferencia($this->input('referencia'))]);
        }

        // "149,90" (coma decimal, habitual en Perú) se acepta como 149.90.
        foreach (['precio', 'precio_comparacion'] as $campo) {
            $precio = $this->input($campo);
            if (is_string($precio) && preg_match('/^\d+,\d{1,2}$/', trim($precio))) {
                $this->merge([$campo => str_replace(',', '.', trim($precio))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'referencia' => ['required', 'string', 'max:50', $this->referenciaUnica()],
            'nombre' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:170', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'descripcion' => ['required', 'string', 'max:20000', $this->descripcionConTexto()],
            'especificaciones' => ['nullable', 'string', $this->especificacionesValidas()],
            // Se escribe en soles; el controller lo pasa a céntimos con bcmath.
            // Mínimo 0.01 y no 0: Izipay no acepta cobros de S/ 0.00. El regex
            // va primero: "1e3" pasa 'numeric' pero revienta bcmul() (500), y
            // "10.555" se truncaba en silencio a 10.55.
            'precio' => ['required', 'regex:/^\d{1,5}(\.\d{1,2})?$/', 'numeric', 'min:0.01', 'max:99999.99'],
            // "Antes S/ X" tachado de la oferta (y del carrito). Opcional, pero
            // si viene tiene que ser mayor al precio: un "antes" menor o igual
            // sería un descuento falso.
            'precio_comparacion' => ['nullable', 'regex:/^\d{1,5}(\.\d{1,2})?$/', 'numeric', 'max:99999.99', 'gt:precio'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'activo' => ['nullable', 'boolean'],
            'destacado' => ['nullable', 'boolean'],
            'oferta' => ['nullable', 'boolean', $this->ofertaVisible()],
            'oferta_reiniciar' => ['nullable', 'boolean'],
            'oferta_titular' => ['nullable', 'required_if:oferta,1,true', 'string', 'max:120'],
            'oferta_bajada' => ['nullable', 'string', 'max:300'],
            'oferta_beneficios' => ['nullable', 'string', $this->beneficiosValidos()],
            'eliminar_imagen' => ['nullable', 'boolean'],
            'imagen' => [$this->esEdicion() ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=200,min_height=200'],
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
            'precio.regex' => 'Escribe el precio con hasta 2 decimales, por ejemplo 149.90.',
            'precio.min' => 'El precio debe ser mayor a S/ 0.00.',
            'precio.max' => 'El precio no puede superar S/ 99,999.99.',
            'precio_comparacion.regex' => 'Escribe el precio regular con hasta 2 decimales, por ejemplo 199.90.',
            'precio_comparacion.max' => 'El precio regular no puede superar S/ 99,999.99.',
            'precio_comparacion.gt' => 'El precio regular debe ser mayor al precio de venta.',
            'oferta_titular.required_if' => 'Escribe el titular de la oferta.',
            'stock.min' => 'El stock no puede ser negativo.',
            'orden.min' => 'El orden no puede ser negativo.',
            'imagen.required' => 'Sube una imagen del producto (JPG, PNG o WEBP · máx. 2 MB).',
            'imagen.uploaded' => 'No se pudo subir la imagen. Debe pesar máx. 2 MB.',
            'imagen.image' => 'El archivo debe ser una imagen JPG, PNG o WEBP.',
            'imagen.mimes' => 'Formato no permitido. Usa JPG, PNG o WEBP.',
            'imagen.max' => 'La imagen no puede pesar más de 2 MB.',
            'imagen.dimensions' => 'La imagen debe medir al menos 200 × 200 px.',
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
            'oferta' => 'oferta del día',
            'oferta_titular' => 'titular',
            'oferta_bajada' => 'bajada',
            'oferta_beneficios' => 'beneficios',
            'precio_comparacion' => 'precio regular',
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

    private function descripcionConTexto(): Closure
    {
        return function (string $attribute, mixed $valor, Closure $fail): void {
            if (is_string($valor) && ! Producto::descripcionTieneTexto($valor)) {
                $fail('Escribe una descripción del producto.');
            }
        };
    }

    /**
     * El repeater manda [{clave, valor}, ...] en JSON. Antes cualquier error
     * (JSON roto, valor sin clave) se descartaba en silencio y el admin
     * perdía datos sin enterarse; ahora cada problema vuelve como 422.
     */
    private function especificacionesValidas(): Closure
    {
        return function (string $attribute, mixed $valor, Closure $fail): void {
            if (! is_string($valor) || trim($valor) === '') {
                return;
            }

            $filas = json_decode($valor, true);

            if (! is_array($filas) || ! array_is_list($filas)) {
                $fail('No se pudieron leer las especificaciones. Vuelve a intentarlo.');

                return;
            }

            if (count($filas) > self::MAX_FILAS_SPECS) {
                $fail('Máximo '.self::MAX_FILAS_SPECS.' filas de especificaciones.');

                return;
            }

            $vistas = [];

            foreach ($filas as $i => $fila) {
                $n = $i + 1;
                $clave = trim((string) (is_array($fila) ? ($fila['clave'] ?? '') : ''));
                $valorFila = trim((string) (is_array($fila) ? ($fila['valor'] ?? '') : ''));

                if ($clave === '' && $valorFila !== '') {
                    $fail("Fila {$n}: falta la clave.");
                }

                if (mb_strlen($clave) > self::MAX_CLAVE_SPEC) {
                    $fail("Fila {$n}: la clave no puede pasar de ".self::MAX_CLAVE_SPEC.' caracteres.');
                }

                if (mb_strlen($valorFila) > self::MAX_VALOR_SPEC) {
                    $fail("Fila {$n}: el valor no puede pasar de ".self::MAX_VALOR_SPEC.' caracteres.');
                }

                if ($clave !== '') {
                    $normalizada = mb_strtolower($clave);

                    if (isset($vistas[$normalizada])) {
                        $fail("Fila {$n}: la clave «{$clave}» ya está en la fila {$vistas[$normalizada]}.");
                    } else {
                        $vistas[$normalizada] = $n;
                    }
                }
            }
        };
    }

    /** La oferta se muestra en /productos: un producto oculto no puede ser la oferta del día. */
    private function ofertaVisible(): Closure
    {
        return function (string $attribute, mixed $valor, Closure $fail): void {
            if ($this->boolean('oferta') && ! $this->boolean('activo')) {
                $fail('Para publicarlo como oferta, activa «Visible en la tienda».');
            }
        };
    }

    /**
     * Las tarjetas 01/02/03 de la oferta llegan en JSON como
     * [{titulo, texto}, ...]. Las filas totalmente vacías se descartan en el
     * controller; una fila a medias vuelve como 422, igual que las specs.
     */
    private function beneficiosValidos(): Closure
    {
        return function (string $attribute, mixed $valor, Closure $fail): void {
            if (! is_string($valor) || trim($valor) === '') {
                return;
            }

            $filas = json_decode($valor, true);

            if (! is_array($filas) || ! array_is_list($filas)) {
                $fail('No se pudieron leer los beneficios. Vuelve a intentarlo.');

                return;
            }

            if (count($filas) > self::MAX_BENEFICIOS_OFERTA) {
                $fail('Máximo '.self::MAX_BENEFICIOS_OFERTA.' beneficios.');

                return;
            }

            foreach ($filas as $i => $fila) {
                $n = $i + 1;
                $titulo = trim((string) (is_array($fila) ? ($fila['titulo'] ?? '') : ''));
                $texto = trim((string) (is_array($fila) ? ($fila['texto'] ?? '') : ''));

                if ($titulo === '' && $texto !== '') {
                    $fail("Beneficio {$n}: falta el título.");
                }

                if (mb_strlen($titulo) > self::MAX_TITULO_BENEFICIO) {
                    $fail("Beneficio {$n}: el título no puede pasar de ".self::MAX_TITULO_BENEFICIO.' caracteres.');
                }

                if (mb_strlen($texto) > self::MAX_TEXTO_BENEFICIO) {
                    $fail("Beneficio {$n}: el texto no puede pasar de ".self::MAX_TEXTO_BENEFICIO.' caracteres.');
                }
            }
        };
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
