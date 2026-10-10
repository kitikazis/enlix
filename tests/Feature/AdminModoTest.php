<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModoTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_es_redirigido_a_login(): void
    {
        $this->post(route('admin.modo.actualizar'), ['modo' => 'prueba'])
            ->assertRedirect('/admin/login');
    }

    public function test_cambia_a_modo_de_prueba_y_lo_recuerda_en_la_sesion(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('admin.dashboard'))
            ->post(route('admin.modo.actualizar'), ['modo' => 'prueba']);

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('admin_modo_prueba', true);
    }

    public function test_cambia_a_modo_real_y_lo_recuerda_en_la_sesion(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['admin_modo_prueba' => true])
            ->from(route('admin.dashboard'))
            ->post(route('admin.modo.actualizar'), ['modo' => 'real']);

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('admin_modo_prueba', false);
    }

    public function test_rechaza_un_valor_de_modo_desconocido(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.modo.actualizar'), ['modo' => 'lo-que-sea'])
            ->assertSessionHasErrors('modo');
    }
}
