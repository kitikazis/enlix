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

    public function test_carrito_vacio_renderiza(): void
    {
        $r = $this->get('/carrito');

        $r->assertOk();
        $r->assertSee('Tu carrito está vacío');
    }

    public function test_carrito_con_items_renderiza(): void
    {
        $producto = $this->producto();
        $this->postJson(route('carrito.items.store'), ['producto_id' => $producto->id, 'cantidad' => 2]);
        $sessionId = Carrito::sole()->session_id;

        $r = $this->withCookie('carrito_session', $sessionId)->get('/carrito');

        $r->assertOk();
        $r->assertSee('GPU de prueba');
        $r->assertSee('S/ 3,000.00', false);
    }
}
