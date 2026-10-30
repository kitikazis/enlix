<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\ImagenProducto;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fotos adicionales, marca y garantía del modal de productos: los datos que
 * alimentan la galería y el encabezado de la página de detalle.
 */
class AdminProductoGaleriaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
    }

    private function producto(array $overrides = []): Producto
    {
        return Producto::create(array_merge([
            'slug' => 'gpu-'.uniqid(),
            'referencia' => 'REF-'.uniqid(),
            'nombre' => 'GPU de prueba',
            'descripcion' => '<p>Descripción.</p>',
            'categoria_id' => Categoria::create(['nombre' => 'GPU', 'slug' => 'gpu-'.uniqid()])->id,
            'precio_centimos' => 150000,
            'stock' => 5,
        ], $overrides));
    }

    private function subirFoto(Producto $producto, ?UploadedFile $archivo = null)
    {
        return $this->post(route('admin.productos.imagenes.store', $producto), [
            'imagen' => $archivo ?? UploadedFile::fake()->image('foto.jpg', 400, 400),
        ], ['Accept' => 'application/json']);
    }

    public function test_subir_foto_la_guarda_con_miniatura_y_la_devuelve(): void
    {
        $producto = $this->producto();

        $this->subirFoto($producto)->assertCreated()->assertJsonStructure(['id', 'url', 'thumb_url']);

        $imagen = $producto->imagenes()->sole();
        Storage::disk('public')->assertExists($imagen->ruta);
        Storage::disk('public')->assertExists(str_replace('productos/', 'productos/thumbs/', $imagen->ruta));
    }

    public function test_las_fotos_nuevas_van_despues_de_las_anteriores(): void
    {
        $producto = $this->producto();

        $this->subirFoto($producto);
        $this->subirFoto($producto);

        $this->assertSame([1, 2], $producto->imagenes()->pluck('orden')->all());
    }

    public function test_no_deja_pasar_del_maximo_de_fotos(): void
    {
        $producto = $this->producto();

        foreach (range(1, Producto::MAX_FOTOS_GALERIA) as $orden) {
            $producto->imagenes()->create(['ruta' => "productos/{$orden}.webp", 'orden' => $orden]);
        }

        $this->subirFoto($producto)
            ->assertStatus(422)
            ->assertJsonValidationErrors('imagen');

        $this->assertSame(Producto::MAX_FOTOS_GALERIA, $producto->imagenes()->count());
    }

    public function test_rechaza_archivos_que_no_son_imagen_con_mensaje_en_espanol(): void
    {
        $this->subirFoto($this->producto(), UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'))
            ->assertStatus(422)
            ->assertJsonPath('errors.imagen.0', 'El archivo debe ser una imagen JPG, PNG o WEBP.');
    }

    public function test_eliminar_foto_borra_el_archivo_y_la_miniatura(): void
    {
        $producto = $this->producto();
        $this->subirFoto($producto);
        $imagen = $producto->imagenes()->sole();

        $this->delete(route('admin.productos.imagenes.destroy', [$producto, $imagen]), [], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertModelMissing($imagen);
        Storage::disk('public')->assertMissing($imagen->ruta);
        Storage::disk('public')->assertMissing(str_replace('productos/', 'productos/thumbs/', $imagen->ruta));
    }

    public function test_no_se_puede_eliminar_la_foto_de_otro_producto(): void
    {
        $producto = $this->producto();
        $imagen = $this->producto()->imagenes()->create(['ruta' => 'productos/ajena.webp', 'orden' => 1]);

        $this->delete(route('admin.productos.imagenes.destroy', [$producto, $imagen]), [], ['Accept' => 'application/json'])
            ->assertNotFound();

        $this->assertModelExists($imagen);
    }

    public function test_invitado_no_puede_subir_ni_eliminar_fotos(): void
    {
        $producto = $this->producto();
        $imagen = $producto->imagenes()->create(['ruta' => 'productos/x.webp', 'orden' => 1]);
        auth()->logout();

        $this->subirFoto($producto)->assertUnauthorized();
        $this->delete(route('admin.productos.imagenes.destroy', [$producto, $imagen]), [], ['Accept' => 'application/json'])
            ->assertUnauthorized();

        $this->assertSame(1, ImagenProducto::count());
    }

    public function test_el_modal_recibe_la_galeria_la_marca_y_la_garantia(): void
    {
        $producto = $this->producto([
            'marca_id' => Marca::create(['nombre' => 'MSI', 'slug' => 'msi-test'])->id,
            'meses_garantia' => 24,
        ]);
        $imagen = $producto->imagenes()->create(['ruta' => 'productos/x.webp', 'orden' => 1]);

        $this->getJson(route('admin.productos.show', $producto))
            ->assertJsonPath('marca', 'MSI')
            ->assertJsonPath('meses_garantia', 24)
            ->assertJsonPath('galeria.0.id', $imagen->id);
    }

    private function guardar(Producto $producto, array $campos)
    {
        return $this->post(route('admin.productos.update', $producto), array_merge([
            '_method' => 'PUT',
            'categoria_id' => $producto->categoria_id,
            'referencia' => $producto->referencia,
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion,
            'precio' => '1500.00',
        ], $campos), ['Accept' => 'application/json']);
    }

    public function test_guardar_marca_nueva_la_crea_y_reutiliza_la_existente(): void
    {
        $producto = $this->producto();
        $otro = $this->producto();

        $this->guardar($producto, ['marca' => 'Acer Nitro', 'meses_garantia' => '12'])->assertOk();
        $this->guardar($otro, ['marca' => 'Acer Nitro'])->assertOk();

        $producto->refresh();
        $this->assertSame('Acer Nitro', $producto->marca->nombre);
        $this->assertSame(12, $producto->meses_garantia);
        $this->assertSame($producto->marca_id, $otro->refresh()->marca_id);
        $this->assertSame(1, Marca::where('nombre', 'Acer Nitro')->count());
    }

    public function test_dejar_la_marca_y_la_garantia_vacias_las_quita(): void
    {
        $producto = $this->producto(['marca_id' => Marca::create(['nombre' => 'MSI', 'slug' => 'msi-test'])->id, 'meses_garantia' => 12]);

        $this->guardar($producto, ['marca' => '', 'meses_garantia' => ''])->assertOk();

        $producto->refresh();
        $this->assertNull($producto->marca_id);
        $this->assertNull($producto->meses_garantia);
    }

    public function test_un_cliente_que_no_manda_marca_ni_garantia_no_las_borra(): void
    {
        $producto = $this->producto(['marca_id' => Marca::create(['nombre' => 'MSI', 'slug' => 'msi-test'])->id, 'meses_garantia' => 12]);

        $this->guardar($producto, [])->assertOk();

        $producto->refresh();
        $this->assertNotNull($producto->marca_id);
        $this->assertSame(12, $producto->meses_garantia);
    }

    public function test_garantia_fuera_de_rango_da_error_en_espanol(): void
    {
        $this->guardar($this->producto(), ['meses_garantia' => '500'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('meses_garantia');
    }
}
