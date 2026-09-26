<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Support\Producto as CatalogoProducto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CRUD de productos para el admin. El precio que se guarda aquí es la
 * ÚNICA fuente de verdad del monto que se cobra en el checkout
 * (IzipayController::formToken lee de App\Support\Producto, que lee de
 * aquí) - por eso valida montos razonables y nunca confía en el navegador
 * para nada que no sea el ID del producto a editar.
 */
class ProductosController extends Controller
{
    public function index(): View
    {
        $productos = Producto::orderBy('orden')->orderBy('id')->get();

        return view('admin.productos.index', ['productos' => $productos]);
    }

    public function create(): View
    {
        return view('admin.productos.form', [
            'producto' => new Producto(['activo' => true, 'stock' => 0]),
            'accion' => route('admin.productos.store'),
            'categorias' => Categoria::orderBy('nombre')->get(),
            'marcas' => Marca::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validado($request);
        $datos['slug'] = $this->slugUnico($datos['nombre']);

        $producto = Producto::create($datos);
        $this->guardarImagenes($producto, $request);
        CatalogoProducto::limpiarCache();

        return redirect()->route('admin.productos.index')->with('exito', 'Producto creado.');
    }

    public function edit(Producto $producto): View
    {
        return view('admin.productos.form', [
            'producto' => $producto->load('imagenes'),
            'accion' => route('admin.productos.update', $producto),
            'categorias' => Categoria::orderBy('nombre')->get(),
            'marcas' => Marca::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $producto->update($this->validado($request, $producto));
        $this->eliminarImagenes($producto, $request);
        $this->guardarImagenes($producto, $request);
        CatalogoProducto::limpiarCache();

        return redirect()->route('admin.productos.index')->with('exito', 'Producto actualizado.');
    }

    /**
     * No se borra nunca un producto: pagos históricos guardan su slug como
     * texto (sin llave foránea) y deben poder seguir mostrándose. Desactivar
     * lo saca de /productos sin perder el historial.
     */
    public function alternarActivo(Producto $producto): RedirectResponse
    {
        $producto->update(['activo' => ! $producto->activo]);
        CatalogoProducto::limpiarCache();

        $mensaje = $producto->activo ? 'Producto activado.' : 'Producto desactivado.';

        return redirect()->route('admin.productos.index')->with('exito', $mensaje);
    }

    private function validado(Request $request, ?Producto $producto = null): array
    {
        // "" en el input de SKU debe tratarse como "sin SKU" (null), no como
        // un valor vacío que choque con la validacion unique de otro
        // producto que tampoco tenga SKU todavia.
        $request->merge(['sku' => $request->filled('sku') ? trim((string) $request->input('sku')) : null]);

        $datos = $request->validate([
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'marca_id' => ['nullable', 'integer', 'exists:marcas,id'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('productos', 'sku')->ignore($producto?->id)],
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion_corta' => ['nullable', 'string', 'max:160'],
            'descripcion' => ['required', 'string', 'max:500'],
            'especificaciones' => ['nullable', 'string', 'max:2000'],
            // El precio se escribe en soles en el formulario; aqui se pasa a
            // centimos con bcmath para no arrastrar errores de coma flotante.
            'precio' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'features' => ['nullable', 'string', 'max:2000'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'activo' => ['nullable', 'boolean'],
            'destacado' => ['nullable', 'boolean'],
            'imagenes' => ['nullable', 'array', 'max:8'],
            'imagenes.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        return [
            'categoria_id' => $datos['categoria_id'] ?? null,
            'marca_id' => $datos['marca_id'] ?? null,
            'sku' => $datos['sku'] ?? null,
            'nombre' => $datos['nombre'],
            'descripcion_corta' => $datos['descripcion_corta'] ?? null,
            'descripcion' => $datos['descripcion'],
            'especificaciones' => $this->especificaciones($datos['especificaciones'] ?? ''),
            'precio_centimos' => (int) bcmul((string) $datos['precio'], '100'),
            'stock' => (int) ($datos['stock'] ?? 0),
            'features' => $this->features($datos['features'] ?? ''),
            'orden' => (int) ($datos['orden'] ?? 0),
            'activo' => $request->boolean('activo'),
            'destacado' => $request->boolean('destacado'),
        ];
    }

    /** Una característica por línea en el textarea. */
    private function features(string $texto): array
    {
        return collect(explode("\n", $texto))
            ->map(fn ($linea) => trim($linea))
            ->filter()
            ->values()
            ->all();
    }

    /** Una especificación "clave: valor" por línea en el textarea. */
    private function especificaciones(string $texto): ?array
    {
        $specs = collect(explode("\n", $texto))
            ->map(fn ($linea) => trim($linea))
            ->filter()
            ->mapWithKeys(function ($linea) {
                [$clave, $valor] = array_pad(explode(':', $linea, 2), 2, '');

                return [trim($clave) => trim($valor)];
            })
            ->filter(fn ($valor, $clave) => $clave !== '')
            ->all();

        return $specs === [] ? null : $specs;
    }

    private function guardarImagenes(Producto $producto, Request $request): void
    {
        if (! $request->hasFile('imagenes')) {
            return;
        }

        $orden = (int) $producto->imagenes()->max('orden');

        foreach ($request->file('imagenes') as $archivo) {
            $ruta = $archivo->store('productos', 'public');

            $producto->imagenes()->create([
                'ruta' => $ruta,
                'orden' => ++$orden,
            ]);
        }
    }

    private function eliminarImagenes(Producto $producto, Request $request): void
    {
        $idsAEliminar = (array) $request->input('eliminar_imagenes', []);

        if ($idsAEliminar === []) {
            return;
        }

        $imagenes = $producto->imagenes()->whereIn('id', $idsAEliminar)->get();

        foreach ($imagenes as $imagen) {
            Storage::disk('public')->delete($imagen->ruta);
            $imagen->delete();
        }
    }

    private function slugUnico(string $nombre): string
    {
        $base = Str::slug($nombre);
        $slug = $base;
        $sufijo = 1;

        while (Producto::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$sufijo);
        }

        return $slug;
    }
}
