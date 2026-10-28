<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductoGuardarRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Support\Producto as CatalogoProducto;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
            'categoria' => $producto->categoria?->nombre,
            'descripcion' => $producto->descripcion,
            'especificaciones' => $producto->especificaciones ?? [],
            'imagen_url' => $producto->imagen_url,
            'tiene_imagen' => $producto->imagen !== null,
            'precio' => number_format($producto->precio_centimos / 100, 2, '.', ''),
            'precio_comparacion' => $producto->precio_comparacion_centimos !== null
                ? number_format($producto->precio_comparacion_centimos / 100, 2, '.', '')
                : '',
            'stock' => $producto->stock,
            'orden' => $producto->orden,
            'activo' => $producto->activo,
            'destacado' => $producto->destacado,
            'oferta' => $producto->tieneOfertaVigente(),
            'oferta_termina_en' => $producto->tieneOfertaVigente() ? $producto->oferta_termina_en->toIso8601String() : null,
            'oferta_titular' => $producto->oferta_titular ?? '',
            'oferta_bajada' => $producto->oferta_bajada ?? '',
            'oferta_beneficios' => $producto->oferta_beneficios ?? [],
        ]);
    }

    public function store(ProductoGuardarRequest $request): JsonResponse
    {
        $datos = $this->datosProducto($request);
        $datos['slug'] = $this->slugUnico($request->input('slug') ?: $datos['nombre']);
        $datos['imagen'] = $this->procesarImagen($request->file('imagen'));

        $datos += $this->datosOferta($request);

        try {
            [$producto, $reemplazados] = DB::transaction(function () use ($datos) {
                $producto = Producto::create($datos);

                return [$producto, $this->quitarOtrasOfertas($producto)];
            });
        } catch (UniqueConstraintViolationException $e) {
            $this->eliminarImagen($datos['imagen']);

            throw $this->errorDeUnicidad($e);
        }

        CatalogoProducto::limpiarCache();

        return $this->respuestaExito($producto->load('categoria'), 'Producto creado.', 201, $reemplazados);
    }

    public function update(ProductoGuardarRequest $request, Producto $producto): JsonResponse
    {
        $datos = $this->datosProducto($request);
        $datos['slug'] = $this->slugUnico($request->input('slug') ?: $datos['nombre'], $producto);
        $imagenAnterior = $producto->imagen;

        if ($request->hasFile('imagen')) {
            $datos['imagen'] = $this->procesarImagen($request->file('imagen'));
        } elseif ($request->boolean('eliminar_imagen')) {
            $datos['imagen'] = null;
        }

        $datos += $this->datosOferta($request, $producto);

        try {
            $reemplazados = DB::transaction(function () use ($producto, $datos) {
                $producto->update($datos);

                return $this->quitarOtrasOfertas($producto);
            });
        } catch (UniqueConstraintViolationException $e) {
            if ($request->hasFile('imagen')) {
                $this->eliminarImagen($datos['imagen']);
            }

            throw $this->errorDeUnicidad($e);
        }

        // La imagen vieja se borra recién cuando el update ya se guardó: si
        // fallara antes, el producto no se queda apuntando a un archivo que
        // ya no existe.
        if (array_key_exists('imagen', $datos) && $imagenAnterior !== $datos['imagen']) {
            $this->eliminarImagen($imagenAnterior);
        }

        CatalogoProducto::limpiarCache();

        return $this->respuestaExito($producto->load('categoria'), 'Producto actualizado.', 200, $reemplazados);
    }

    /**
     * Desactivar saca el producto de /productos sin tocar nada mas (sigue
     * en el listado admin, se puede reactivar). No confundir con destroy().
     */
    public function alternarActivo(Request $request, Producto $producto): JsonResponse|RedirectResponse
    {
        $producto->update(['activo' => ! $producto->activo]);
        CatalogoProducto::limpiarCache();

        $mensaje = $producto->activo ? 'Producto activado.' : 'Producto desactivado.';

        if ($request->expectsJson()) {
            return $this->respuestaExito($producto->load('categoria'), $mensaje);
        }

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
    public function destroy(Request $request, Producto $producto): JsonResponse|RedirectResponse
    {
        // activo=false tambien: find() (App\Support\Producto) sigue
        // encontrando productos eliminados a proposito (ver su docblock),
        // asi que sin esto alguien con el slug a mano podria iniciar una
        // compra NUEVA de un producto ya "eliminado" via formToken().
        $producto->update(['activo' => false]);
        $producto->delete();
        CatalogoProducto::limpiarCache();

        if ($request->expectsJson()) {
            return response()->json([
                'mensaje' => 'Producto enviado a la papelera.',
                'id' => $producto->id,
                'url_restaurar' => route('admin.productos.restaurar', $producto),
                'total_eliminados' => Producto::onlyTrashed()->count(),
            ]);
        }

        return redirect()->route('admin.productos.index')->with('exito', 'Producto eliminado.');
    }

    /**
     * Recupera un producto eliminado (deshace destroy()). Queda inactivo a
     * proposito: el admin decide cuando volver a publicarlo con "Activar"
     * desde el listado normal, en vez de que reaparezca ya visible en
     * /productos sin que nadie lo haya revisado. Responde JSON cuando se
     * restaura desde el modal (referencia que choca con la papelera).
     */
    public function restaurar(Request $request, Producto $producto): JsonResponse|RedirectResponse
    {
        $producto->restore();
        CatalogoProducto::limpiarCache();

        if ($request->expectsJson()) {
            return $this->respuestaExito($producto->load('categoria'), 'Producto recuperado. Sigue inactivo hasta que lo actives.');
        }

        return redirect()->route('admin.productos.papelera')->with('exito', 'Producto recuperado. Sigue inactivo hasta que lo actives desde el listado.');
    }

    /**
     * Chequeo en vivo (fetch) mientras el admin escribe la Referencia en el
     * modal. Usa Producto::conReferencia(), igual que ProductoGuardarRequest,
     * así "disponible" aquí garantiza que el POST no devuelva 422 por la
     * referencia. Si choca con un producto de la papelera, devuelve la URL
     * para restaurarlo desde el mismo modal.
     */
    public function verificarReferencia(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'referencia' => ['required', 'string', 'max:50'],
            'producto_id' => ['nullable', 'integer'],
        ]);

        $referencia = Producto::normalizarReferencia($datos['referencia']);
        $existente = Producto::conReferencia($referencia, isset($datos['producto_id']) ? (int) $datos['producto_id'] : null);

        return response()->json([
            'referencia' => $referencia,
            'disponible' => $existente === null,
            'eliminado' => $existente?->trashed() ?? false,
            'producto' => $existente ? [
                'id' => $existente->id,
                'nombre' => $existente->nombre,
                'url_restaurar' => $existente->trashed() ? route('admin.productos.restaurar', $existente) : null,
            ] : null,
        ]);
    }

    /**
     * $reemplazados: productos que dejaron de ser la oferta del día por
     * este guardado. Sus filas vuelven también (filas_extra) para que el
     * listado no siga mostrando la etiqueta "Oferta" en el anterior.
     */
    private function respuestaExito(Producto $producto, string $mensaje, int $status = 200, array $reemplazados = []): JsonResponse
    {
        return response()->json([
            'mensaje' => $mensaje,
            'html' => view('admin.productos._fila', ['producto' => $producto])->render(),
            'filas_extra' => collect($reemplazados)
                ->map(fn (Producto $p) => view('admin.productos._fila', ['producto' => $p->load('categoria')])->render())
                ->all(),
        ], $status);
    }

    /**
     * Campos de la oferta del día. Solo se tocan si el formulario manda
     * "oferta" (el modal siempre lo manda): cualquier otro cliente que no
     * conozca la oferta no la borra sin querer.
     *
     * Publicar abre una ventana de 24 h reales desde AHORA. Editar una
     * oferta que ya está vigente NO reinicia el contador (corregir una
     * errata no regala horas), salvo que se pida "oferta_reiniciar".
     * Desmarcarla cierra la ventana pero conserva los textos.
     */
    private function datosOferta(ProductoGuardarRequest $request, ?Producto $producto = null): array
    {
        if (! $request->has('oferta')) {
            return [];
        }

        $datos = $request->validated();

        $campos = [
            'oferta_titular' => $this->textoONulo($datos['oferta_titular'] ?? null),
            'oferta_bajada' => $this->textoONulo($datos['oferta_bajada'] ?? null),
            'oferta_beneficios' => $this->beneficios($datos['oferta_beneficios'] ?? null),
        ];

        if (! $request->boolean('oferta')) {
            return $campos + ['oferta_inicia_en' => null, 'oferta_termina_en' => null];
        }

        if ($producto?->tieneOfertaVigente() && ! $request->boolean('oferta_reiniciar')) {
            return $campos;
        }

        return $campos + [
            'oferta_inicia_en' => now(),
            'oferta_termina_en' => now()->addHours(Producto::HORAS_OFERTA),
        ];
    }

    /**
     * Una sola oferta del día a la vez: si este producto quedó como oferta
     * vigente, se cierra la de cualquier otro. Devuelve los afectados.
     */
    private function quitarOtrasOfertas(Producto $producto): array
    {
        if (! $producto->tieneOfertaVigente()) {
            return [];
        }

        $otros = Producto::whereKeyNot($producto->id)
            ->where('oferta_termina_en', '>', now())
            ->get();

        foreach ($otros as $otro) {
            $otro->update(['oferta_inicia_en' => null, 'oferta_termina_en' => null]);
        }

        return $otros->all();
    }

    private function textoONulo(?string $texto): ?string
    {
        $texto = trim((string) $texto);

        return $texto === '' ? null : $texto;
    }

    /** Igual que especificaciones(): [{titulo, texto}, ...] sin las filas vacías. */
    private function beneficios(?string $json): ?array
    {
        $filas = json_decode($json ?? '', true);

        if (! is_array($filas)) {
            return null;
        }

        $beneficios = collect($filas)
            ->map(fn ($fila) => [
                'titulo' => trim((string) ($fila['titulo'] ?? '')),
                'texto' => trim((string) ($fila['texto'] ?? '')),
            ])
            ->filter(fn ($fila) => $fila['titulo'] !== '')
            ->values()
            ->all();

        return $beneficios === [] ? null : $beneficios;
    }

    private function datosProducto(ProductoGuardarRequest $request): array
    {
        $datos = $request->validated();

        return [
            'categoria_id' => $datos['categoria_id'],
            'referencia' => $datos['referencia'],
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'especificaciones' => $this->especificaciones($datos['especificaciones'] ?? null),
            // El precio se escribe en soles en el formulario; aqui se pasa a
            // centimos con bcmath para no arrastrar errores de coma flotante.
            'precio_centimos' => (int) bcmul((string) $datos['precio'], '100'),
            'stock' => (int) ($datos['stock'] ?? 0),
            'orden' => (int) ($datos['orden'] ?? 0),
            'activo' => $request->boolean('activo'),
            'destacado' => $request->boolean('destacado'),
        ] + ($request->has('precio_comparacion') ? [
            'precio_comparacion_centimos' => filled($datos['precio_comparacion'] ?? null)
                ? (int) bcmul((string) $datos['precio_comparacion'], '100')
                : null,
        ] : []);
    }

    /**
     * Dos admins guardando a la vez pueden pasar ambos la validación con la
     * misma referencia/slug; el índice único de la BD frena al segundo y
     * aquí se convierte en un 422 normal en vez de un 500.
     */
    private function errorDeUnicidad(UniqueConstraintViolationException $e): ValidationException
    {
        $campo = str_contains($e->getMessage(), 'slug') ? 'slug' : 'referencia';

        return ValidationException::withMessages([
            $campo => $campo === 'slug'
                ? 'Otro producto acaba de tomar este slug. Vuelve a guardar.'
                : 'Otro producto acaba de tomar esta referencia. Usa otra.',
        ]);
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

    /**
     * Slug a partir del nombre (o del slug editado a mano) con sufijo
     * incremental si ya existe. withTrashed: el índice productos_slug_unique
     * también cuenta los productos de la papelera, sin esto un slug de un
     * producto eliminado terminaba en un 500 al insertar.
     */
    private function slugUnico(string $texto, ?Producto $producto = null): string
    {
        $base = trim(Str::limit(Str::slug($texto), 160, ''), '-') ?: 'producto';
        $slug = $base;
        $sufijo = 1;

        while (Producto::withTrashed()->where('slug', $slug)->when($producto?->id, fn ($q, $id) => $q->whereKeyNot($id))->exists()) {
            $slug = $base.'-'.(++$sufijo);
        }

        return $slug;
    }
}
