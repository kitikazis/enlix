<?php

namespace Tests\Feature;

use App\Models\Pago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagosTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_es_redirigido_a_login_al_entrar_a_pagos(): void
    {
        $this->get(route('admin.pagos.index'))
            ->assertRedirect('/admin/login');
    }

    public function test_login_con_credenciales_invalidas_falla(): void
    {
        $user = User::factory()->create();

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'contraseña-incorrecta',
        ])->assertRedirect();

        $this->assertGuest();
    }

    private function crearPago(array $overrides = []): Pago
    {
        return Pago::create(array_merge([
            'producto' => 'plan-web-basico',
            'email' => 'cliente@example.com',
            'monto' => 9900,
            'moneda' => 'PEN',
            'izipay_order_id' => 'ENX-TEST-'.uniqid(),
            'estado' => 'pendiente',
        ], $overrides));
    }

    public function test_login_correcto_permite_ver_pagos(): void
    {
        $user = User::factory()->create();
        $this->crearPago(['estado' => 'pagado']);

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.pagos.index'));

        $this->assertAuthenticatedAs($user);

        $this->get(route('admin.pagos.index'))
            ->assertOk()
            ->assertSee('pagado');
    }

    public function test_filtro_por_estado_solo_muestra_esos_pagos(): void
    {
        $user = User::factory()->create();
        $this->crearPago(['estado' => 'pagado', 'email' => 'pagado@example.com']);
        $this->crearPago(['estado' => 'rechazado', 'email' => 'rechazado@example.com']);

        $this->actingAs($user);

        $response = $this->get(route('admin.pagos.index', ['estado' => 'pagado']));

        $response->assertOk();
        $response->assertSee('pagado@example.com');
        $response->assertDontSee('rechazado@example.com');
    }

    public function test_logout_cierra_la_sesion(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }
}
