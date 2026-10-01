<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EstadoPago;
use App\Models\Pago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagosTest extends TestCase
{
    use RefreshDatabase;

    private function crearPago(EstadoPago $estado, int $monto, string $email = 'cliente@gmail.com'): Pago
    {
        return Pago::create([
            'producto' => 'plan-web-basico',
            'email' => $email,
            'monto' => $monto,
            'moneda' => 'PEN',
            'izipay_order_id' => 'ENX-PAGOS-'.uniqid(),
            'estado' => $estado,
        ]);
    }

    public function test_invitado_es_redirigido_a_login(): void
    {
        $this->get(route('admin.pagos'))->assertRedirect('/admin/login');
    }

    public function test_admin_autenticado_ve_el_listado_completo(): void
    {
        $user = User::factory()->create();
        $this->crearPago(EstadoPago::Pagado, 9900, 'visible@gmail.com');

        $this->actingAs($user)
            ->get(route('admin.pagos'))
            ->assertOk()
            ->assertSee('visible@gmail.com');
    }

    public function test_filtra_por_estado(): void
    {
        $user = User::factory()->create();
        $this->crearPago(EstadoPago::Pagado, 9900, 'pagado@gmail.com');
        $this->crearPago(EstadoPago::Rechazado, 9900, 'rechazado@gmail.com');

        $this->actingAs($user)
            ->get(route('admin.pagos', ['estado' => EstadoPago::Pagado->value]))
            ->assertSee('pagado@gmail.com')
            ->assertDontSee('rechazado@gmail.com');
    }

    public function test_excluir_prueba_activo_por_defecto_oculta_pagos_de_prueba(): void
    {
        $user = User::factory()->create();
        $this->crearPago(EstadoPago::Pagado, 9900, 'real@gmail.com');
        $this->crearPago(EstadoPago::Pagado, 9900, 'prueba@example.com');

        $this->actingAs($user)
            ->get(route('admin.pagos'))
            ->assertSee('real@gmail.com')
            ->assertDontSee('prueba@example.com');
    }

    public function test_excluir_prueba_en_cero_muestra_todo(): void
    {
        $user = User::factory()->create();
        $this->crearPago(EstadoPago::Pagado, 9900, 'prueba@example.com');

        $this->actingAs($user)
            ->get(route('admin.pagos', ['excluir_prueba' => '0']))
            ->assertSee('prueba@example.com');
    }
}
