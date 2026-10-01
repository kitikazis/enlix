<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
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

    private function categoria(): Categoria
    {
        return Categoria::create(['nombre' => 'Tarjetas gráficas', 'slug' => 'tarjetas-graficas-'.uniqid()]);
    }

    /** Payload válido completo para el modal; los tests solo pisan lo que les importa. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'categoria_id' => $this->categoria()->id,
            'referencia' => 'REF-'.uniqid(),
            'nombre' => 'Producto de prueba',
            'descripcion' => '<p>Descripción de prueba.</p>',
            'especificaciones' => json_encode([]),
            'precio' => '149.90',
            'stock' => 5,
            'activo' => '1',
            'imagen' => UploadedFile::fake()->image('foto.jpg'),
        ], $overrides);
    }

    public function test_invitado_es_redirigido_a_login(): void
    {
        $this->get(route('admin.productos.index'))->assertRedirect('/admin/login');
    }

    public function test_las_rutas_de_productos_exigen_auth_de_forma_incondicional(): void
    {
        // Aqui se edita el precio real del checkout: no debe existir ningun
        // camino que las deje sin autenticacion.
        $nombres = [
            'admin.productos.index',
            'admin.productos.store',
            'admin.productos.show',
            'admin.productos.update',
            'admin.productos.alternar-activo',
            'admin.productos.verificar-referencia',
            'admin.productos.destroy',
            'admin.productos.papelera',
            'admin.productos.restaurar',
        ];

        foreach ($nombres as $nombre) {
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

    public function test_el_script_de_productosapp_lleva_el_nonce_del_csp(): void
    {
        // Sin nonce, el CSP bloquea TODO el <script> inline que define
        // productosApp(): el modal queda "roto" (abrirEditar, toastMensaje,
        // etc. salen "is not defined" en consola) aunque el HTML se vea bien.
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('admin.productos.index'));

        $csp = $response->headers->get('Content-Security-Policy');
        preg_match("/'nonce-([a-zA-Z0-9]+)'/", $csp, $m);
        $this->assertNotEmpty($m, 'El CSP debe declarar un nonce para script-src.');

        // No solo que el nonce aparezca en la pagina (el layout ya trae el
        // suyo propio): que sea justo el <script> de productosApp el que lo
        // lleve.
        $this->assertMatchesRegularExpression(
            '/<script nonce="'.preg_quote($m[1], '/').'">\s*function productosApp/',
            $response->getContent()
        );
    }

    public function test_crea_un_producto_convirtiendo_soles_a_centimos(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.productos.store'), $this->payload([
            'nombre' => 'Plan Nuevo',
            'precio' => '149.90',
        ]), ['Accept' => 'application/json']);

        $response->assertCreated();

        $producto = Producto::where('nombre', 'Plan Nuevo')->first();
        $this->assertNotNull($producto);
        $this->assertSame(14990, $producto->precio_centimos);
        $this->assertSame('plan-nuevo', $producto->slug);
        $this->assertTrue($producto->activo);
        $this->assertNotNull($producto->imagen);
    }

    public function test_no_permite_precio_en_cero_o_negativo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.productos.store'), $this->payload([
            'nombre' => 'Plan Gratis',
            'precio' => '0',
        ]), ['Accept' => 'application/json'])->assertJsonValidationErrors('precio');

        $this->assertDatabaseMissing('productos', ['nombre' => 'Plan Gratis']);
    }

    public function test_exige_categoria_referencia_e_imagen(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.productos.store'), [
            'nombre' => 'Sin nada',
            'descripcion' => 'x',
            'precio' => '10.00',
        ], ['Accept' => 'application/json'])
            ->assertJsonValidationErrors(['categoria_id', 'referencia', 'imagen']);
    }

    public function test_actualiza_el_precio_de_un_producto_existente(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();

        $response = $this->actingAs($user)->post(
            route('admin.productos.update', $producto),
            array_merge($this->payload([
                'nombre' => $producto->nombre,
                'referencia' => $producto->referencia,
                'precio' => '75.00',
            ]), ['_method' => 'PUT']),
            ['Accept' => 'application/json']
        );

        $response->assertOk();
        $this->assertSame(7500, $producto->fresh()->precio_centimos);
    }

    public function test_actualizar_sin_nueva_imagen_conserva_la_existente(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();
        $producto->update(['imagen' => 'productos/existente.webp']);

        $payload = $this->payload([
            'nombre' => $producto->nombre,
            'referencia' => $producto->referencia,
        ]);
        unset($payload['imagen']);

        $this->actingAs($user)->post(
            route('admin.productos.update', $producto),
            array_merge($payload, ['_method' => 'PUT']),
            ['Accept' => 'application/json']
        )->assertOk();

        $this->assertSame('productos/existente.webp', $producto->fresh()->imagen);
    }

    public function test_el_checkout_usa_el_precio_actualizado_desde_el_admin(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();

        $this->actingAs($user)->post(
            route('admin.productos.update', $producto),
            array_merge($this->payload([
                'nombre' => $producto->nombre,
                'referencia' => $producto->referencia,
                'precio' => '55.00',
            ]), ['_method' => 'PUT']),
            ['Accept' => 'application/json']
        );

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

    public function test_eliminar_hace_soft_delete_y_lo_saca_del_catalogo_publico(): void
    {
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();

        $this->actingAs($user)
            ->delete(route('admin.productos.destroy', $producto))
            ->assertRedirect(route('admin.productos.index'));

        // Soft delete: la fila sigue en la base de datos...
        $this->assertDatabaseHas('productos', ['id' => $producto->id]);
        $this->assertNotNull($producto->fresh()->deleted_at);
        $this->assertFalse($producto->fresh()->activo);
        // ...pero desaparece de las consultas normales (admin, catálogo).
        $this->assertArrayNotHasKey('plan-web-basico', CatalogoProducto::items());
        $this->assertNull(Producto::find($producto->id));
    }

    public function test_no_se_puede_iniciar_una_compra_de_un_producto_eliminado(): void
    {
        // Aunque "eliminar" no es lo mismo que "desactivar", alguien con el
        // slug a mano no debe poder iniciar una compra nueva (ver
        // ProductosController::destroy()).
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();
        $this->actingAs($user)->delete(route('admin.productos.destroy', $producto));

        $this->postJson(route('izipay.form-token'), [
            'producto' => 'plan-web-basico',
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'email' => 'juan@example.com',
            'phone_number' => '+51999999999',
            'identity_code' => '12345678',
        ])->assertStatus(404);
    }

    public function test_la_papelera_muestra_productos_eliminados(): void
    {
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();
        $this->actingAs($user)->delete(route('admin.productos.destroy', $producto));

        $this->actingAs($user)
            ->get(route('admin.productos.papelera'))
            ->assertOk()
            ->assertSee($producto->nombre);
    }

    public function test_la_papelera_no_muestra_productos_activos(): void
    {
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();

        $this->actingAs($user)
            ->get(route('admin.productos.papelera'))
            ->assertDontSee($producto->nombre);
    }

    public function test_recuperar_devuelve_el_producto_al_listado_pero_inactivo(): void
    {
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();
        $this->actingAs($user)->delete(route('admin.productos.destroy', $producto));

        $this->actingAs($user)
            ->patch(route('admin.productos.restaurar', $producto))
            ->assertRedirect(route('admin.productos.papelera'));

        $recuperado = Producto::find($producto->id);
        $this->assertNotNull($recuperado, 'El producto debe volver a aparecer en consultas normales.');
        $this->assertNull($recuperado->deleted_at);
        // Inactivo a proposito: el admin decide cuando volver a publicarlo.
        $this->assertFalse($recuperado->activo);
        $this->assertArrayNotHasKey($producto->slug, CatalogoProducto::items());

        $this->actingAs($user)
            ->get(route('admin.productos.index'))
            ->assertOk()
            ->assertSee($producto->nombre);
    }

    public function test_un_producto_eliminado_sigue_apareciendo_en_pagos_historicos(): void
    {
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();
        $nombreOriginal = $producto->nombre;

        \App\Models\Pago::create([
            'producto' => 'plan-web-basico',
            'email' => 'historico@gmail.com',
            'monto' => 9900,
            'moneda' => 'PEN',
            'izipay_order_id' => 'ENX-HIST-'.uniqid(),
            'estado' => \App\Enums\EstadoPago::Pagado,
        ]);

        $this->actingAs($user)->delete(route('admin.productos.destroy', $producto));

        $this->actingAs($user)
            ->get(route('admin.pagos'))
            ->assertOk()
            ->assertSee($nombreOriginal);
    }

    public function test_eliminar_la_imagen_existente_la_borra_de_verdad(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();

        $this->actingAs($user)->post(route('admin.productos.update', $producto), array_merge(
            $this->payload(['nombre' => $producto->nombre, 'referencia' => $producto->referencia]),
            ['_method' => 'PUT']
        ), ['Accept' => 'application/json'])->assertOk();

        $rutaImagen = $producto->fresh()->imagen;
        $this->assertNotNull($rutaImagen);
        Storage::disk('public')->assertExists($rutaImagen);

        $payload = $this->payload(['nombre' => $producto->nombre, 'referencia' => $producto->referencia]);
        unset($payload['imagen']);
        $payload['eliminar_imagen'] = '1';

        $this->actingAs($user)->post(
            route('admin.productos.update', $producto),
            array_merge($payload, ['_method' => 'PUT']),
            ['Accept' => 'application/json']
        )->assertOk();

        $this->assertNull($producto->fresh()->imagen);
        Storage::disk('public')->assertMissing($rutaImagen);
    }

    public function test_crea_un_producto_con_categoria_referencia_stock_y_especificaciones(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $categoria = Categoria::create(['nombre' => 'Tarjetas gráficas', 'slug' => 'tarjetas-graficas']);

        $this->actingAs($user)->post(route('admin.productos.store'), $this->payload([
            'categoria_id' => $categoria->id,
            'referencia' => 'GPU-001',
            'nombre' => 'RTX de prueba',
            'stock' => 5,
            'especificaciones' => json_encode([
                ['clave' => 'Socket', 'valor' => 'AM5'],
                ['clave' => 'VRAM', 'valor' => '8GB'],
            ]),
        ]), ['Accept' => 'application/json'])->assertCreated();

        $producto = Producto::where('referencia', 'GPU-001')->first();
        $this->assertNotNull($producto);
        $this->assertSame($categoria->id, $producto->categoria_id);
        $this->assertSame(5, $producto->stock);
        $this->assertSame([
            ['clave' => 'Socket', 'valor' => 'AM5'],
            ['clave' => 'VRAM', 'valor' => '8GB'],
        ], $producto->especificaciones);
    }

    public function test_descarta_filas_de_especificaciones_sin_clave(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.productos.store'), $this->payload([
            'nombre' => 'Con specs a medias',
            'especificaciones' => json_encode([
                ['clave' => 'Socket', 'valor' => 'AM5'],
                ['clave' => '  ', 'valor' => 'se descarta'],
            ]),
        ]), ['Accept' => 'application/json'])->assertCreated();

        $producto = Producto::where('nombre', 'Con specs a medias')->first();
        $this->assertSame([['clave' => 'Socket', 'valor' => 'AM5']], $producto->especificaciones);
    }

    public function test_no_permite_dos_productos_con_la_misma_referencia(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Producto::create([
            'slug' => 'existente', 'nombre' => 'Existente', 'descripcion' => 'x',
            'precio_centimos' => 1000, 'referencia' => 'DUP-001', 'activo' => true,
        ]);

        $this->actingAs($user)->post(route('admin.productos.store'), $this->payload([
            'referencia' => 'DUP-001',
            'nombre' => 'Otro producto',
        ]), ['Accept' => 'application/json'])->assertJsonValidationErrors('referencia');

        $this->assertDatabaseMissing('productos', ['nombre' => 'Otro producto']);
    }

    public function test_rechaza_un_archivo_que_no_es_imagen(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.productos.store'), $this->payload([
            'nombre' => 'Con archivo malo',
            'imagen' => UploadedFile::fake()->create('virus.exe', 100),
        ]), ['Accept' => 'application/json'])->assertJsonValidationErrors('imagen');

        $this->assertDatabaseMissing('productos', ['nombre' => 'Con archivo malo']);
    }

    public function test_procesa_la_imagen_a_webp_y_genera_miniatura(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.productos.store'), $this->payload([
            'nombre' => 'Con imagen',
            'imagen' => UploadedFile::fake()->image('foto.jpg', 2000, 1500),
        ]), ['Accept' => 'application/json'])->assertCreated();

        $producto = Producto::where('nombre', 'Con imagen')->first();
        $this->assertNotNull($producto->imagen);
        $this->assertStringEndsWith('.webp', $producto->imagen);
        Storage::disk('public')->assertExists($producto->imagen);
        Storage::disk('public')->assertExists(str_replace('productos/', 'productos/thumbs/', $producto->imagen));
    }

    public function test_borra_la_imagen_anterior_al_reemplazarla(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.productos.store'), $this->payload([
            'nombre' => 'Con imagen a reemplazar',
        ]), ['Accept' => 'application/json'])->assertCreated();

        $producto = Producto::where('nombre', 'Con imagen a reemplazar')->first();
        $rutaVieja = $producto->imagen;
        $rutaMiniaturaVieja = str_replace('productos/', 'productos/thumbs/', $rutaVieja);

        $payload = $this->payload([
            'nombre' => $producto->nombre,
            'referencia' => $producto->referencia,
            'imagen' => UploadedFile::fake()->image('nueva.jpg'),
        ]);

        $this->actingAs($user)->post(
            route('admin.productos.update', $producto),
            array_merge($payload, ['_method' => 'PUT']),
            ['Accept' => 'application/json']
        )->assertOk();

        Storage::disk('public')->assertMissing($rutaVieja);
        Storage::disk('public')->assertMissing($rutaMiniaturaVieja);
        Storage::disk('public')->assertExists($producto->fresh()->imagen);
    }

    public function test_muestra_los_datos_de_un_producto_en_json_para_el_modal_de_edicion(): void
    {
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();

        $this->actingAs($user)->getJson(route('admin.productos.show', $producto))
            ->assertOk()
            ->assertJson(['id' => $producto->id, 'nombre' => $producto->nombre, 'referencia' => $producto->referencia]);
    }

    public function test_verifica_disponibilidad_de_referencia(): void
    {
        $user = User::factory()->create();
        $producto = Producto::where('slug', 'plan-web-basico')->first();

        $this->actingAs($user)
            ->getJson(route('admin.productos.verificar-referencia', ['referencia' => $producto->referencia]))
            ->assertOk()
            ->assertJson(['disponible' => false]);

        $this->actingAs($user)
            ->getJson(route('admin.productos.verificar-referencia', ['referencia' => $producto->referencia, 'producto_id' => $producto->id]))
            ->assertOk()
            ->assertJson(['disponible' => true]);

        $this->actingAs($user)
            ->getJson(route('admin.productos.verificar-referencia', ['referencia' => 'NUEVA-REF-XYZ']))
            ->assertOk()
            ->assertJson(['disponible' => true]);
    }
}
