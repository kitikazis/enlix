<?php

namespace Tests\Feature;

use App\Models\Pago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_es_redirigido_a_login_al_entrar_al_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
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

    public function test_login_correcto_permite_ver_el_dashboard(): void
    {
        $user = User::factory()->create();
        $this->crearPago(['estado' => 'pagado']);

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('pagado');
    }

    public function test_logout_cierra_la_sesion(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }
}
