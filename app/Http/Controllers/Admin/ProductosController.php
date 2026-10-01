<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use App\Support\Producto as CatalogoProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Intervention\Image\ImageManager;

/**
 * CRUD de productos para el admin (modal en la página de listado, sin
 * páginas separadas de crear/editar). El precio que se guarda aquí es la
 * ÚNICA fuente de verdad del monto que se cobra en el checkout
 * (IzipayController::formToken lee de App\Support\Producto, que lee de
 * aquí) - por eso valida montos razonables y nunca confía en el navegador
 * para nada que no sea el ID del producto a editar.
 */
class ProductosController extends Controller
{
    private const ANCHO_IMAGEN = 1200;

    private const ANCHO_MINIATURA = 300;

    public function index(): View
    {
        $productos = Producto::with('categoria')->orderBy('orden')->orderBy('id')->get();

        return view('admin.productos.index', [
            'productos' => $productos,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'totalEliminados' => Producto::onlyTrashed()->count(),
        ]);
    }

    /** Papelera: productos eliminados (soft delete), para poder recuperarlos. */
    public function papelera(): View
    {
        $productos = Producto::onlyTrashed()->with('categoria')->orderByDesc('deleted_at')->get();

        return view('admin.productos.papelera', [
            'productos' => $productos,
        ]);
    }

    /** JSON con los datos completos de un producto, para precargar el modal de edición. */
    public function show(Producto $producto): JsonResponse
    {
        return response()->json([
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'slug' => $producto->slug,
            'referencia' => $producto->referencia,
            'categoria_id' => $producto->categoria_id,
            'descripcion' => $producto->descripcion,
            'especificaciones' => $producto->especificaciones ?? [],
            'imagen_url' => $producto->imagen_url,
            'tiene_imagen' => $producto->imagen !== null,
            'precio' => number_format($producto->precio_centimos / 100, 2, '.', ''),
            'stock' => $producto->stock,
            'orden' => $producto->orden,
            'activo' => $producto->activo,
            'destacado' => $producto->destacado,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $datos = $this->validado($request);
        $datos['slug'] = $this->slugUnico($request->input('slug') ?: $datos['nombre']);

        if ($request->hasFile('imagen')) {
            $datos['imagen'] = $this->procesarImagen($request->file('imagen'));
        }

        $producto = Producto::create($datos);
        CatalogoProducto::limpiarCache();

        return $this->respuestaExito($producto->load('categoria'), 'Producto creado.', 201);
    }

    public function update(Request $request, Producto $producto): JsonResponse|RedirectResponse
    {
        $datos = $this->validado($request, $producto);
        $datos['slug'] = $this->slugUnico($request->input('slug') ?: $datos['nombre'], $producto);

        if ($request->hasFile('imagen')) {
            $this->eliminarImagen($producto->imagen);
            $datos['imagen'] = $this->procesarImagen($request->file('imagen'));
        } elseif ($request->boolean('eliminar_imagen') && $producto->imagen !== null) {
            $this->eliminarImagen($producto->imagen);
            $datos['imagen'] = null;
        }

        $producto->update($datos);
        CatalogoProducto::limpiarCache();

        return $this->respuestaExito($producto->load('categoria'), 'Producto actualizado.');
    }

    /**
     * Desactivar saca el producto de /productos sin tocar nada mas (sigue
     * en el listado admin, se puede reactivar). No confundir con destroy().
     */
    public function alternarActivo(Producto $producto): RedirectResponse
    {
        $producto->update(['activo' => ! $producto->activo]);
        CatalogoProducto::limpiarCache();

        $mensaje = $producto->activo ? 'Producto activado.' : 'Producto desactivado.';

        return redirect()->route('admin.productos.index')->with('exito', $mensaje);
    }

    /**
     * Soft delete (Producto usa SoftDeletes): desaparece del listado admin y
     * del catálogo público, pero la fila sigue en la base de datos. No se
     * borra nunca de verdad ni se tocan sus imágenes - items_pedido.producto_id
     * ya es nullOnDelete pensando en esto, y cada línea de pedido guarda su
     * propio snapshot de nombre/precio, así que el historial no depende de
     * que el producto siga vivo.
     */
    public function destroy(Producto $producto): RedirectResponse
    {
        // activo=false tambien: find() (App\Support\Producto) sigue
        // encontrando productos eliminados a proposito (ver su docblock),
        // asi que sin esto alguien con el slug a mano podria iniciar una
        // compra NUEVA de un producto ya "eliminado" via formToken().
        $producto->update(['activo' => false]);
        $producto->delete();
        CatalogoProducto::limpiarCache();

        return redirect()->route('admin.productos.index')->with('exito', 'Producto eliminado.');
    }

    /**
     * Recupera un producto eliminado (deshace destroy()). Queda inactivo a
     * proposito: el admin decide cuando volver a publicarlo con "Activar"
     * desde el listado normal, en vez de que reaparezca ya visible en
     * /productos sin que nadie lo haya revisado.
     */
    public function restaurar(Producto $producto): RedirectResponse
    {
        $producto->restore();
        CatalogoProducto::limpiarCache();

        return redirect()->route('admin.productos.papelera')->with('exito', 'Producto recuperado. Sigue inactivo hasta que lo actives desde el listado.');
    }

    /** Chequeo en vivo (fetch) mientras el admin escribe la Referencia en el modal. */
    public function verificarReferencia(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'referencia' => ['required', 'string', 'max:50'],
            'producto_id' => ['nullable', 'integer'],
        ]);

        $disponible = ! Producto::where('referencia', $datos['referencia'])
            ->when($datos['producto_id'] ?? null, fn ($query, $id) => $query->where('id', '!=', $id))
            ->exists();

        return response()->json(['disponible' => $disponible]);
    }

    private function respuestaExito(Producto $producto, string $mensaje, int $status = 200): JsonResponse|RedirectResponse
    {
        return response()->json([
            'mensaje' => $mensaje,
            'html' => view('admin.productos._fila', ['producto' => $producto])->render(),
        ], $status);
    }

    private function validado(Request $request, ?Producto $producto = null): array
    {
        $datos = $request->validate([
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'referencia' => ['required', 'string', 'max:50', Rule::unique('productos', 'referencia')->ignore($producto?->id)],
            'nombre' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:170', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'descripcion' => ['required', 'string', 'max:20000'],
            'especificaciones' => ['nullable', 'string'],
            // El precio se escribe en soles en el formulario; aqui se pasa a
            // centimos con bcmath para no arrastrar errores de coma flotante.
            'precio' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'activo' => ['nullable', 'boolean'],
            'destacado' => ['nullable', 'boolean'],
            'imagen' => [$producto?->exists ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        return [
            'categoria_id' => $datos['categoria_id'],
            'referencia' => trim($datos['referencia']),
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'especificaciones' => $this->especificaciones($datos['especificaciones'] ?? null),
            'precio_centimos' => (int) bcmul((string) $datos['precio'], '100'),
            'stock' => (int) ($datos['stock'] ?? 0),
            'orden' => (int) ($datos['orden'] ?? 0),
            'activo' => $request->boolean('activo'),
            'destacado' => $request->boolean('destacado'),
        ];
    }

    /** El repeater del modal manda un JSON con [{clave, valor}, ...]; se descarta cualquier fila sin clave. */
    private function especificaciones(?string $json): ?array
    {
        $filas = json_decode($json ?? '', true);

        if (! is_array($filas)) {
            return null;
        }

        $specs = collect($filas)
            ->map(fn ($fila) => [
                'clave' => trim((string) ($fila['clave'] ?? '')),
                'valor' => trim((string) ($fila['valor'] ?? '')),
            ])
            ->filter(fn ($fila) => $fila['clave'] !== '')
            ->values()
            ->all();

        return $specs === [] ? null : $specs;
    }

    /**
     * Convierte a webp y redimensiona a máx. 1200px de ancho (sin recortar
     * ni agrandar imágenes pequeñas), más una miniatura de 300px para el
     * listado. Nombre único (uuid) para no chocar con imágenes viejas.
     */
    private function procesarImagen(UploadedFile $archivo): string
    {
        $nombre = (string) Str::uuid();
        $rutaImagen = "productos/{$nombre}.webp";
        $rutaMiniatura = "productos/thumbs/{$nombre}.webp";

        Storage::disk('public')->makeDirectory('productos');
        Storage::disk('public')->makeDirectory('productos/thumbs');

        $manager = ImageManager::gd();

        $manager->read($archivo->getRealPath())
            ->scaleDown(width: self::ANCHO_IMAGEN)
            ->toWebp(82)
            ->save(Storage::disk('public')->path($rutaImagen));

        $manager->read($archivo->getRealPath())
            ->scaleDown(width: self::ANCHO_MINIATURA)
            ->toWebp(82)
            ->save(Storage::disk('public')->path($rutaMiniatura));

        return $rutaImagen;
    }

    private function eliminarImagen(?string $ruta): void
    {
        if ($ruta === null) {
            return;
        }

        Storage::disk('public')->delete($ruta);
        Storage::disk('public')->delete(Str::replaceFirst('productos/', 'productos/thumbs/', $ruta));
    }

    private function slugUnico(string $nombre, ?Producto $producto = null): string
    {
        $base = Str::slug($nombre);
        $slug = $base;
        $sufijo = 1;

        while (Producto::where('slug', $slug)->when($producto?->id, fn ($q, $id) => $q->where('id', '!=', $id))->exists()) {
            $slug = $base.'-'.(++$sufijo);
        }

        return $slug;
    }
}
