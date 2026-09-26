<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\ImagenProducto;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\User;
use App\Support\Producto as CatalogoProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductosTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_es_redirigido_a_login(): void
    {
        $this->get(route('admin.productos.index'))->assertRedirect('/admin/login');
    }

    public function test_las_rutas_de_productos_exigen_auth_de_forma_incondicional(): void
    {
        // Aqui se edita el precio real del checkout: no debe existir ningun
        // camino que las deje sin autenticacion (dashboard y productos
        // exigen 'auth' siempre, sin bypass). Se verifica el middleware
        // registrado en vez de manipular env() en caliente, porque las
        // rutas ya quedan fijadas al arrancar la aplicacion.
        foreach (['admin.productos.index', 'admin.productos.create', 'admin.productos.store'] as $nombre) {
            $ruta = Route::getRoutes()->getByName($nombre);
            $this->assertContains('auth', $ruta->gatherMiddleware(), "La ruta {$nombre} debe exigir 'auth' siempre.");
        }
    }

    public function test_admin_autenticado_ve_la_lista_de_productos(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.productos.index'))
            ->assertOk()
            ->assertSee('Plan Web Básico');
    }

    public function test_crea_un_producto_convirtiendo_soles_a_centimos(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.productos.store'), [
            'nombre' => 'Plan Nuevo',
            'descripcion' => 'Descripción de prueba.',
            'precio' => '149.90',
            'features' => "Feature uno\nFeature dos",
            'orden' => 5,
            'activo' => '1',
        ]);

        $response->assertRedirect(route('admin.productos.index'));

        $producto = Producto::where('nombre', 'Plan Nuevo')->first();
        $this->assertNotNull($producto);
        $this->assertSame(14990, $producto->precio_centimos);
        $this->assertSame('plan-nuevo', $producto->slug);
        $this->assertSame(['Feature uno', 'Feature dos'], $producto->features);
        $this->assertTrue($producto->activo);
    }

    public function test_no_permite_precio_en_cero_o_negativo(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.productos.store'), [
            'nombre' => 'Plan Gratis',
            'descripcion' => 'x',
            'precio' => '0',
        ])->assertSessionHasErrors('precio');

        $this->assertDatabaseMissing('productos', ['nombre' => 'Plan Gratis']);
    }

    public function test_actualiza_el_precio_de_un_producto_existente(): void
    {
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();

        $this->actingAs($user)->put(route('admin.productos.update', $producto), [
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion,
            'precio' => '75.00',
            'orden' => $producto->orden,
            'activo' => '1',
        ])->assertRedirect(route('admin.productos.index'));

        $this->assertSame(7500, $producto->fresh()->precio_centimos);
    }

    public function test_el_checkout_usa_el_precio_actualizado_desde_el_admin(): void
    {
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();

        $this->actingAs($user)->put(route('admin.productos.update', $producto), [
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion,
            'precio' => '55.00',
            'orden' => $producto->orden,
            'activo' => '1',
        ]);

        $item = CatalogoProducto::find('plan-web-basico');
        $this->assertSame(5500, $item['precio_centimos']);
    }

    public function test_desactivar_un_producto_lo_saca_del_catalogo_publico(): void
    {
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();

        $this->actingAs($user)
            ->patch(route('admin.productos.alternar-activo', $producto))
            ->assertRedirect(route('admin.productos.index'));

        $this->assertFalse($producto->fresh()->activo);
        $this->assertArrayNotHasKey('plan-web-basico', CatalogoProducto::items());
    }

    public function test_no_se_puede_iniciar_una_compra_de_un_producto_desactivado(): void
    {
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();
        $this->actingAs($user)->patch(route('admin.productos.alternar-activo', $producto));

        $response = $this->postJson(route('izipay.form-token'), [
            'producto' => 'plan-web-basico',
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'email' => 'juan@example.com',
            'phone_number' => '+51999999999',
            'identity_code' => '12345678',
        ]);

        $response->assertStatus(404);
    }

    public function test_reactivar_lo_devuelve_al_catalogo(): void
    {
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();

        $this->actingAs($user)->patch(route('admin.productos.alternar-activo', $producto));
        $this->actingAs($user)->patch(route('admin.productos.alternar-activo', $producto));

        $this->assertTrue($producto->fresh()->activo);
        $this->assertArrayHasKey('plan-web-basico', CatalogoProducto::items());
    }

    public function test_crea_un_producto_con_categoria_marca_sku_stock_y_especificaciones(): void
    {
        $user = User::factory()->create();
        $categoria = Categoria::create(['nombre' => 'Tarjetas gráficas', 'slug' => 'tarjetas-graficas']);
        $marca = Marca::create(['nombre' => 'NVIDIA', 'slug' => 'nvidia']);

        $this->actingAs($user)->post(route('admin.productos.store'), [
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'sku' => 'GPU-001',
            'nombre' => 'RTX de prueba',
            'descripcion' => 'x',
            'especificaciones' => "Socket: AM5\nVRAM: 8GB",
            'precio' => '10.00',
            'stock' => 5,
        ])->assertRedirect(route('admin.productos.index'));

        $producto = Producto::where('sku', 'GPU-001')->first();
        $this->assertNotNull($producto);
        $this->assertSame($categoria->id, $producto->categoria_id);
        $this->assertSame($marca->id, $producto->marca_id);
        $this->assertSame(5, $producto->stock);
        $this->assertSame(['Socket' => 'AM5', 'VRAM' => '8GB'], $producto->especificaciones);
    }

    public function test_no_permite_dos_productos_con_el_mismo_sku(): void
    {
        $user = User::factory()->create();
        Producto::create([
            'slug' => 'existente', 'nombre' => 'Existente', 'descripcion' => 'x',
            'precio_centimos' => 1000, 'sku' => 'DUP-001', 'activo' => true,
        ]);

        $this->actingAs($user)->post(route('admin.productos.store'), [
            'sku' => 'DUP-001',
            'nombre' => 'Otro producto',
            'descripcion' => 'x',
            'precio' => '10.00',
        ])->assertSessionHasErrors('sku');

        $this->assertDatabaseMissing('productos', ['nombre' => 'Otro producto']);
    }

    public function test_dos_productos_pueden_tener_sku_vacio(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.productos.store'), [
            'nombre' => 'Sin SKU uno', 'descripcion' => 'x', 'precio' => '10.00',
        ])->assertRedirect(route('admin.productos.index'));

        $this->actingAs($user)->post(route('admin.productos.store'), [
            'nombre' => 'Sin SKU dos', 'descripcion' => 'x', 'precio' => '10.00',
        ])->assertSessionDoesntHaveErrors('sku');
    }

    public function test_sube_imagenes_al_crear_un_producto(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.productos.store'), [
            'nombre' => 'Con imagenes', 'descripcion' => 'x', 'precio' => '10.00',
            'imagenes' => [
                UploadedFile::fake()->image('foto1.jpg'),
                UploadedFile::fake()->image('foto2.png'),
            ],
        ])->assertRedirect(route('admin.productos.index'));

        $producto = Producto::where('nombre', 'Con imagenes')->first();
        $this->assertSame(2, $producto->imagenes()->count());
        Storage::disk('public')->assertExists($producto->imagenes()->first()->ruta);
    }

    public function test_rechaza_un_archivo_que_no_es_imagen(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.productos.store'), [
            'nombre' => 'Con archivo malo', 'descripcion' => 'x', 'precio' => '10.00',
            'imagenes' => [UploadedFile::fake()->create('virus.exe', 100)],
        ])->assertSessionHasErrors('imagenes.0');

        $this->assertDatabaseMissing('productos', ['nombre' => 'Con archivo malo']);
    }

    public function test_elimina_una_imagen_existente_al_editar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();
        $imagen = ImagenProducto::create([
            'producto_id' => $producto->id,
            'ruta' => UploadedFile::fake()->image('vieja.jpg')->store('productos', 'public'),
            'orden' => 1,
        ]);

        $this->actingAs($user)->put(route('admin.productos.update', $producto), [
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion,
            'precio' => '99.00',
            'eliminar_imagenes' => [$imagen->id],
        ])->assertRedirect(route('admin.productos.index'));

        $this->assertDatabaseMissing('imagenes_producto', ['id' => $imagen->id]);
        Storage::disk('public')->assertMissing($imagen->ruta);
    }

    public function test_renderiza_el_form_de_crear(): void
    {
        $user = User::factory()->create();
        Categoria::create(['nombre' => 'Tarjetas gráficas', 'slug' => 'tarjetas-graficas']);

        $this->actingAs($user)->get(route('admin.productos.create'))
            ->assertOk()
            ->assertSee('Tarjetas gráficas')
            ->assertSee('SKU');
    }

    public function test_renderiza_el_form_de_editar_con_imagenes_existentes(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();
        ImagenProducto::create([
            'producto_id' => $producto->id,
            'ruta' => UploadedFile::fake()->image('actual.jpg')->store('productos', 'public'),
            'orden' => 1,
        ]);

        $this->actingAs($user)->get(route('admin.productos.edit', $producto))
            ->assertOk()
            ->assertSee('Eliminar');
    }
}
