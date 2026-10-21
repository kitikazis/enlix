<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EstadoReclamo;
use App\Models\Reclamo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReclamosTest extends TestCase
{
    use RefreshDatabase;

    private function reclamo(array $overrides = []): Reclamo
    {
        return Reclamo::create(array_merge([
            'nombre_consumidor' => 'Juan Perez',
            'tipo_documento_consumidor' => 'DNI',
            'numero_documento_consumidor' => '12345678',
            'domicilio_consumidor' => 'Av. Siempre Viva 123',
            'telefono_consumidor' => '+51999999999',
            'correo_consumidor' => 'juan@example.com',
            'tipo_bien' => 'producto',
            'descripcion_bien' => 'NVIDIA GeForce RTX 4060',
            'tipo' => 'reclamo',
            'detalle' => 'El producto llegó dañado.',
            'pedido_consumidor' => 'Solicito el cambio.',
            'estado' => EstadoReclamo::Pendiente,
            'acepto_privacidad_en' => now(),
            'fecha_limite_respuesta' => Reclamo::calcularFechaLimite(),
        ], $overrides));
    }

    public function test_invitado_es_redirigido_a_login(): void
    {
        $this->get(route('admin.reclamos.index'))->assertRedirect('/admin/login');
    }

    public function test_lista_los_reclamos(): void
    {
        $user = User::factory()->create();
        $this->reclamo();

        $r = $this->actingAs($user)->get(route('admin.reclamos.index'));

        $r->assertOk();
        $r->assertSee('Juan Perez');
        $r->assertSee('000001', false);
    }

    public function test_muestra_el_detalle_de_un_reclamo(): void
    {
        $user = User::factory()->create();
        $reclamo = $this->reclamo();

        $r = $this->actingAs($user)->get(route('admin.reclamos.show', $reclamo));

        $r->assertOk();
        $r->assertSee('Av. Siempre Viva 123');
        $r->assertSee('El producto llegó dañado.');
    }

    public function test_marca_un_reclamo_como_atendido_registrando_la_respuesta(): void
    {
        $user = User::factory()->create();
        $reclamo = $this->reclamo();

        $r = $this->actingAs($user)->patch(route('admin.reclamos.atender', $reclamo), [
            'respuesta' => '  Se cambió el producto por uno nuevo y se avisó por correo.  ',
        ]);

        $r->assertRedirect();
        $reclamo->refresh();
        $this->assertSame(EstadoReclamo::Atendido, $reclamo->estado);
        $this->assertSame('Se cambió el producto por uno nuevo y se avisó por correo.', $reclamo->respuesta);
        $this->assertNotNull($reclamo->respondido_en);

        $this->actingAs($user)->get(route('admin.reclamos.show', $reclamo))
            ->assertSee('Se cambió el producto por uno nuevo')
            ->assertSee('dentro del plazo')
            ->assertDontSee('Responder y marcar como atendido');
    }

    public function test_no_se_puede_atender_sin_respuesta(): void
    {
        $user = User::factory()->create();
        $reclamo = $this->reclamo();
        $url = route('admin.reclamos.show', $reclamo);

        $this->actingAs($user)->from($url)->patch(route('admin.reclamos.atender', $reclamo), ['respuesta' => 'corta'])
            ->assertRedirect($url)
            ->assertSessionHasErrors(['respuesta' => 'La respuesta debe tener al menos 10 caracteres.']);

        $this->assertSame(EstadoReclamo::Pendiente, $reclamo->fresh()->estado);
    }

    public function test_un_reclamo_atendido_no_se_vuelve_a_responder(): void
    {
        $user = User::factory()->create();
        $reclamo = $this->reclamo();
        $this->actingAs($user)->patch(route('admin.reclamos.atender', $reclamo), ['respuesta' => 'Primera respuesta registrada.']);

        $this->actingAs($user)->patch(route('admin.reclamos.atender', $reclamo), ['respuesta' => 'Intento de pisar la respuesta.'])
            ->assertSessionHas('status', 'Este reclamo ya estaba atendido.');

        $this->assertSame('Primera respuesta registrada.', $reclamo->fresh()->respuesta);
    }

    public function test_el_badge_del_menu_cuenta_solo_pendientes(): void
    {
        $user = User::factory()->create();
        $this->reclamo();
        $this->reclamo(['correo_consumidor' => 'otro@example.com', 'estado' => EstadoReclamo::Atendido]);

        $r = $this->actingAs($user)->get(route('admin.dashboard'));

        $r->assertOk();
        $r->assertSee('Reclamos');
    }
}
