<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use App\Support\Producto as CatalogoProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            $ruta = \Illuminate\Support\Facades\Route::getRoutes()->getByName($nombre);
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
}
