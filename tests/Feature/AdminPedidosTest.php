<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EstadoPago;
use App\Enums\MetodoPago;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPedidosTest extends TestCase
{
    use RefreshDatabase;

    private function crearPedido(array $overrides = []): Pedido
    {
        return Pedido::create(array_merge([
            'codigo' => 'ENX-TEST-'.uniqid(),
            'nombre_cliente' => 'Juan Perez',
            'email' => 'juan@example.com',
            'telefono' => '+51999999999',
            'tipo_documento' => 'DNI',
            'numero_documento' => '12345678',
            'tipo_comprobante' => 'boleta',
            'metodo_entrega' => 'recojo',
            'subtotal_centimos' => 15000,
            'costo_envio_centimos' => 0,
            'descuento_centimos' => 0,
            'total_centimos' => 15000,
            'estado_pago' => EstadoPago::Pendiente,
        ], $overrides));
    }

    public function test_invitado_es_redirigido_a_login(): void
    {
        $this->get(route('admin.pedidos.index'))->assertRedirect('/admin/login');
    }

    public function test_admin_autenticado_ve_el_listado_de_pedidos(): void
    {
        $user = User::factory()->create();
        $this->crearPedido(['codigo' => 'ENX-VISIBLE-001']);

        $this->actingAs($user)->get(route('admin.pedidos.index'))
            ->assertOk()
            ->assertSee('ENX-VISIBLE-001')
            ->assertSee('Juan Perez');
    }

    public function test_filtra_por_estado(): void
    {
        $user = User::factory()->create();
        $this->crearPedido(['codigo' => 'ENX-PAGADO-001', 'estado_pago' => EstadoPago::Pagado]);
        $this->crearPedido(['codigo' => 'ENX-RECHAZADO-001', 'estado_pago' => EstadoPago::Rechazado]);

        $respuesta = $this->actingAs($user)
            ->get(route('admin.pedidos.index', ['estado' => EstadoPago::Pagado->value]));

        $respuesta->assertOk()->assertSee('ENX-PAGADO-001')->assertDontSee('ENX-RECHAZADO-001');
    }

    public function test_filtra_por_cliente_nombre_email_o_codigo(): void
    {
        $user = User::factory()->create();
        $this->crearPedido(['codigo' => 'ENX-ANA-001', 'nombre_cliente' => 'Ana Torres', 'email' => 'ana@example.com']);
        $this->crearPedido(['codigo' => 'ENX-LUIS-001', 'nombre_cliente' => 'Luis Diaz', 'email' => 'luis@example.com']);

        $respuesta = $this->actingAs($user)->get(route('admin.pedidos.index', ['cliente' => 'ana']));

        $respuesta->assertOk()->assertSee('ENX-ANA-001')->assertDontSee('ENX-LUIS-001');
    }

    public function test_invitado_no_puede_ver_el_detalle(): void
    {
        $pedido = $this->crearPedido();

        $this->get(route('admin.pedidos.show', $pedido))->assertRedirect('/admin/login');
    }

    public function test_admin_ve_el_detalle_con_items_cliente_y_pago(): void
    {
        $user = User::factory()->create();
        $pedido = $this->crearPedido([
            'izipay_order_id' => 'ENX-DETALLE-001',
            'transaction_uuid' => 'uuid-abc',
            'card_brand' => 'VISA',
            'card_masked_pan' => '455788XXXXXX8317',
            'metodo_pago' => MetodoPago::Tarjeta,
        ]);

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => null,
            'sku' => 'GPU-001',
            'nombre' => 'GPU de prueba',
            'precio_unitario_centimos' => 150000,
            'cantidad' => 1,
            'subtotal_centimos' => 150000,
        ]);

        $this->actingAs($user)->get(route('admin.pedidos.show', $pedido))
            ->assertOk()
            ->assertSee($pedido->codigo)
            ->assertSee('Juan Perez')
            ->assertSee('GPU de prueba')
            ->assertSee('GPU-001')
            ->assertSee('VISA')
            ->assertSee('Tarjeta');
    }
}
