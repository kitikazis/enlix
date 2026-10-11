<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Carrito;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarritoTest extends TestCase
{
    use RefreshDatabase;

    private function producto(int $stock = 5): Producto
    {
        return Producto::create([
            'slug' => 'gpu-test',
            'referencia' => 'GPU-TEST',
            'nombre' => 'GPU de prueba',
            'descripcion' => 'x',
            'precio_centimos' => 150000,
            'stock' => $stock,
            'activo' => true,
        ]);
    }

    public function test_agregar_crea_item_y_setea_cookie(): void
    {
        $producto = $this->producto();

        $r = $this->postJson(route('carrito.items.store'), ['producto_id' => $producto->id, 'cantidad' => 2]);

        $r->assertOk();
        $r->assertJson(['ok' => true, 'cantidad_total' => 2, 'subtotal_centimos' => 300000]);
        $r->assertCookie('carrito_session');
    }

    public function test_agregar_de_nuevo_suma_cantidad_no_duplica_fila(): void
    {
        $producto = $this->producto();

        $this->postJson(route('carrito.items.store'), ['producto_id' => $producto->id, 'cantidad' => 2]);
        $sessionId = Carrito::sole()->session_id;

        // Los helpers *Json() no reenvian cookies salvo withCredentials() -
        // asi imitan fetch() en un request cross-origin; en el navegador real
        // (mismo origen) las cookies SI viajan solas.
        $r = $this->withCredentials()->withCookie('carrito_session', $sessionId)
            ->postJson(route('carrito.items.store'), ['producto_id' => $producto->id, 'cantidad' => 1]);

        $r->assertOk();
        $r->assertJsonCount(1, 'items');
        $this->assertSame(3, $r->json('cantidad_total'));
        $this->assertSame(1, Carrito::count());
    }

    public function test_no_deja_agregar_mas_del_stock_disponible(): void
    {
        $producto = $this->producto(stock: 2);

        $r = $this->postJson(route('carrito.items.store'), ['producto_id' => $producto->id, 'cantidad' => 3]);

        $r->assertStatus(422);
        $r->assertJson(['ok' => false]);
    }

    public function test_actualizar_y_eliminar_item(): void
    {
        $producto = $this->producto(stock: 10);

        $add = $this->postJson(route('carrito.items.store'), ['producto_id' => $producto->id, 'cantidad' => 1]);
        $itemId = $add->json('items.0.id');
        $sessionId = Carrito::sole()->session_id;
        $cookies = $this->withCredentials()->withCookie('carrito_session', $sessionId);

        $update = $cookies->patchJson(route('carrito.items.update', $itemId), ['cantidad' => 4]);
        $update->assertOk();
        $this->assertSame(4, $update->json('cantidad_total'));

        $delete = $cookies->deleteJson(route('carrito.items.destroy', $itemId));
        $delete->assertOk();
        $this->assertSame(0, $delete->json('cantidad_total'));
    }

    public function test_no_se_puede_editar_item_de_otro_carrito(): void
    {
        $producto = $this->producto(stock: 10);

        $add = $this->postJson(route('carrito.items.store'), ['producto_id' => $producto->id, 'cantidad' => 1]);
        $itemId = $add->json('items.0.id');

        Carrito::create(['session_id' => 'ajeno-de-verdad']);

        $r = $this->withCredentials()->withCookie('carrito_session', 'ajeno-de-verdad')
            ->patchJson(route('carrito.items.update', $itemId), ['cantidad' => 99]);

        $r->assertStatus(404);
    }

    public function test_productos_renderiza_con_producto_en_stock(): void
    {
        $this->producto();

        $r = $this->get('/productos');

        $r->assertOk();
        $r->assertSee('Agregar al carrito');
        $r->assertSee('GPU de prueba');
    }

    public function test_productos_renderiza_boton_agotado_sin_stock(): void
    {
        $this->producto(stock: 0);

        $r = $this->get('/productos');

        $r->assertOk();
        $r->assertSee('Agotado');
    }

    public function test_productos_no_muestra_un_producto_inactivo(): void
    {
        $producto = $this->producto();
        $producto->update(['activo' => false]);

        $r = $this->get('/productos');

        $r->assertOk();
        $r->assertDontSee('GPU de prueba');
    }

    public function test_no_se_puede_agregar_al_carrito_un_producto_inactivo_por_peticion_directa(): void
    {
        $producto = $this->producto();
        $producto->update(['activo' => false]);

        $r = $this->postJson(route('carrito.items.store'), ['producto_id' => $producto->id, 'cantidad' => 1]);

        $r->assertStatus(404);
        $r->assertJson(['ok' => false]);
    }

    public function test_carrito_vacio_renderiza(): void
    {
        $r = $this->get('/carrito');

        $r->assertOk();
        $r->assertSee('Tu carrito está vacío');
    }

    public function test_carrito_con_items_renderiza(): void
    {
        // El Total ahora lo calcula el JS en el navegador (depende de qué
        // quede marcado con el checkbox), así que el servidor ya no imprime
        // un "S/ 3,000.00" fijo: lo que sí debe venir en el HTML es el precio
        // unitario y los data-* que ese cálculo necesita.
        $producto = $this->producto();
        $this->postJson(route('carrito.items.store'), ['producto_id' => $producto->id, 'cantidad' => 2]);
        $sessionId = Carrito::sole()->session_id;

        $r = $this->withCookie('carrito_session', $sessionId)->get('/carrito');

        $r->assertOk();
        $r->assertSee('GPU de prueba');
        $r->assertSee('S/ 1,500.00', false);
        $r->assertSee('data-precio-unitario-centimos="150000"', false);
        $r->assertSee('Máx 5 unidades', false);
    }

    public function test_carrito_oculta_resumen_y_boton_de_pago_cuando_esta_vacio(): void
    {
        $r = $this->get('/carrito');

        $r->assertOk();
        $r->assertSee('Tu carrito está vacío.');
        $r->assertSee('Ver productos');
        $r->assertDontSee('Continuar compra');
        $r->assertDontSee('Resumen de la orden');
    }

    public function test_el_contador_del_header_no_cuenta_items_no_disponibles(): void
    {
        // El badge del header (aria-label "Ver carrito (N productos)") y el
        // título "Carrito (N productos)" de /carrito deben coincidir: ambos
        // excluyen lo agotado/desactivado, no solo lo que sigue disponible.
        $disponible = $this->producto(stock: 5);
        $seAgota = Producto::create([
            'slug' => 'gpu-badge-test', 'referencia' => 'GPU-BADGE', 'nombre' => 'GPU badge test',
            'descripcion' => 'x', 'precio_centimos' => 90000, 'stock' => 2, 'activo' => true,
        ]);

        $this->postJson(route('carrito.items.store'), ['producto_id' => $disponible->id, 'cantidad' => 3]);
        $sessionId = Carrito::sole()->session_id;
        $this->withCookie('carrito_session', $sessionId)
            ->postJson(route('carrito.items.store'), ['producto_id' => $seAgota->id, 'cantidad' => 2]);

        $seAgota->update(['stock' => 0]);

        $r = $this->withCookie('carrito_session', $sessionId)->get('/productos');

        $r->assertOk();
        $r->assertSee('Ver carrito (3 productos)', false);
    }

    public function test_carrito_separa_productos_no_disponibles_en_su_propia_seccion(): void
    {
        $disponible = $this->producto(stock: 5);
        $seAgota = Producto::create([
            'slug' => 'gpu-se-agota', 'referencia' => 'GPU-SE-AGOTA', 'nombre' => 'GPU que se agota',
            'descripcion' => 'x', 'precio_centimos' => 90000, 'stock' => 2, 'activo' => true,
        ]);

        $this->postJson(route('carrito.items.store'), ['producto_id' => $disponible->id, 'cantidad' => 1]);
        $sessionId = Carrito::sole()->session_id;
        $this->withCookie('carrito_session', $sessionId)
            ->postJson(route('carrito.items.store'), ['producto_id' => $seAgota->id, 'cantidad' => 1]);

        // Se agota DESPUES de haberlo agregado (otro cliente compró lo que quedaba).
        $seAgota->update(['stock' => 0]);

        $r = $this->withCookie('carrito_session', $sessionId)->get('/carrito');

        $r->assertOk();
        $r->assertSee('Productos no disponibles (1)');
        $r->assertSee('Agotado');
        $r->assertSee('Ver productos similares');
    }

    public function test_el_modal_de_carrito_agregado_esta_disponible_en_productos(): void
    {
        // Un solo componente, incluido en el layout público: debe estar
        // presente aunque todavía no se haya agregado nada (se abre con JS
        // via el evento 'cart:added', nunca server-side).
        $this->producto();

        $r = $this->get('/productos');

        $r->assertOk();
        $r->assertSee('data-modal-carrito', false);
        $r->assertSee('role="dialog"', false);
        $r->assertSee('aria-labelledby="enlixModalCarritoTitulo"', false);
    }

    public function test_el_boton_agregar_expone_imagen_y_precio_anterior_para_el_modal(): void
    {
        $producto = $this->producto();
        $producto->update(['precio_comparacion_centimos' => 199900]);

        $r = $this->get('/productos');

        $r->assertOk();
        $r->assertSee('data-imagen-url="'.$producto->imagen_url.'"', false);
        $r->assertSee('data-precio-comparacion-centimos="199900"', false);
    }

    public function test_el_boton_agregar_no_expone_precio_anterior_si_no_tiene(): void
    {
        $this->producto();

        $r = $this->get('/productos');

        $r->assertOk();
        $r->assertDontSee('data-precio-comparacion-centimos', false);
    }
}
