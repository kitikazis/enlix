<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EstadoPago;
use App\Models\Pago;
use App\Models\User;
use App\Support\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_es_redirigido_a_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect('/admin/login');
    }

    private function crearPago(EstadoPago $estado, int $monto, string $producto = 'plan-web-basico'): Pago
    {
        return Pago::create([
            'producto' => $producto,
            'email' => 'cliente@example.com',
            'monto' => $monto,
            'moneda' => 'PEN',
            'izipay_order_id' => 'ENX-DASH-'.uniqid(),
            'estado' => $estado,
        ]);
    }

    public function test_suma_los_ingresos_solo_de_pagos_realmente_pagados(): void
    {
        $user = User::factory()->create();
        $this->crearPago(EstadoPago::Pagado, 9900);
        $this->crearPago(EstadoPago::Pagado, 19900);
        $this->crearPago(EstadoPago::Rechazado, 34900);
        $this->crearPago(EstadoPago::Pendiente, 9900);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('S/ 298.00'); // 99 + 199, no cuenta el rechazado ni el pendiente
    }

    public function test_calcula_la_tasa_de_conversion(): void
    {
        $user = User::factory()->create();
        $this->crearPago(EstadoPago::Pagado, 9900);
        $this->crearPago(EstadoPago::Rechazado, 9900);
        $this->crearPago(EstadoPago::Rechazado, 9900);
        $this->crearPago(EstadoPago::Rechazado, 9900);

        // 1 de 4 pagado = 25%
        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertSee('25%');
    }

    public function test_agrupa_ingresos_por_producto(): void
    {
        $user = User::factory()->create();
        $this->crearPago(EstadoPago::Pagado, 9900, 'plan-web-basico');
        $this->crearPago(EstadoPago::Pagado, 9900, 'plan-web-basico');
        $this->crearPago(EstadoPago::Pagado, 34900, 'plan-web-empresarial');

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertSee('plan-web-basico');
        $response->assertSee('S/ 198.00');
        $response->assertSee('plan-web-empresarial');
        $response->assertSee('S/ 349.00');
    }

    public function test_sin_pagos_no_revienta_y_muestra_ceros(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('S/ 0.00');
    }

    public function test_muestra_el_conteo_de_productos_activos(): void
    {
        $user = User::factory()->create();
        $producto = \App\Models\Producto::where('slug', 'plan-web-basico')->first();
        $this->actingAs($user)->patch(route('admin.productos.alternar-activo', $producto));

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertSee('2 activos de 3 en total');
    }
}
