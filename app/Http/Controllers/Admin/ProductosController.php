<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Support\Producto as CatalogoProducto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            'producto' => new Producto(['activo' => true]),
            'accion' => route('admin.productos.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validado($request);
        $datos['slug'] = $this->slugUnico($datos['nombre']);

        Producto::create($datos);
        CatalogoProducto::limpiarCache();

        return redirect()->route('admin.productos.index')->with('exito', 'Producto creado.');
    }

    public function edit(Producto $producto): View
    {
        return view('admin.productos.form', [
            'producto' => $producto,
            'accion' => route('admin.productos.update', $producto),
        ]);
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $producto->update($this->validado($request));
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

    /** @return array{nombre: string, descripcion: string, precio_centimos: int, features: array<int, string>, orden: int, activo: bool} */
    private function validado(Request $request): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['required', 'string', 'max:500'],
            // El precio se escribe en soles en el formulario; aqui se pasa a
            // centimos con bcmath para no arrastrar errores de coma flotante.
            'precio' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'features' => ['nullable', 'string', 'max:2000'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ]);

        return [
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'precio_centimos' => (int) bcmul($datos['precio'], '100'),
            'features' => $this->features($datos['features'] ?? ''),
            'orden' => (int) ($datos['orden'] ?? 0),
            'activo' => $request->boolean('activo'),
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
