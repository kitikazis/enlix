<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Reclamo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LibroReclamacionesTest extends TestCase
{
    use RefreshDatabase;

    private function datosReclamo(array $overrides = []): array
    {
        return array_merge([
            'nombre_consumidor' => 'Juan Perez',
            'tipo_documento_consumidor' => 'DNI',
            'numero_documento_consumidor' => '12345678',
            'domicilio_consumidor' => 'Av. Siempre Viva 123',
            'telefono_consumidor' => '+51999999999',
            'correo_consumidor' => 'juan@example.com',
            'tipo_bien' => 'producto',
            'descripcion_bien' => 'NVIDIA GeForce RTX 4060',
            'monto' => '1500.00',
            'tipo' => 'reclamo',
            'detalle' => 'El producto llegó dañado.',
            'pedido_consumidor' => 'Solicito el cambio del producto.',
            'acepta_privacidad' => '1',
        ], $overrides);
    }

    public function test_pagina_del_formulario_responde_ok(): void
    {
        $this->get(route('libro-reclamaciones.index'))->assertOk()->assertSee('Libro de Reclamaciones');
    }

    public function test_registra_un_reclamo_envia_copia_y_muestra_constancia(): void
    {
        Mail::fake();

        $r = $this->post(route('libro-reclamaciones.store'), $this->datosReclamo());

        $r->assertOk();
        $r->assertSee('fue registrado', false);
        $r->assertSee('000001', false);

        $this->assertDatabaseHas('reclamos', [
            'nombre_consumidor' => 'Juan Perez',
            'descripcion_bien' => 'NVIDIA GeForce RTX 4060',
            'monto_centimos' => 150000,
            'estado' => 'pendiente',
        ]);

        $reclamo = Reclamo::sole();
        $this->assertNotNull($reclamo->acepto_privacidad_en);
        $this->assertNotNull($reclamo->fecha_limite_respuesta);

        Mail::assertQueued(\App\Mail\ReclamoRegistrado::class, fn ($mail) => $mail->hasTo('juan@example.com'));
    }

    public function test_el_correlativo_es_el_id_formateado(): void
    {
        Mail::fake();

        $this->post(route('libro-reclamaciones.store'), $this->datosReclamo());
        $this->post(route('libro-reclamaciones.store'), $this->datosReclamo(['correo_consumidor' => 'otro@example.com']));

        $reclamos = Reclamo::orderBy('id')->get();
        $this->assertSame('000001', $reclamos[0]->numero_correlativo);
        $this->assertSame('000002', $reclamos[1]->numero_correlativo);
    }

    public function test_exige_aceptar_la_politica_de_privacidad(): void
    {
        Mail::fake();

        $r = $this->post(route('libro-reclamaciones.store'), $this->datosReclamo(['acepta_privacidad' => false]));

        $r->assertSessionHasErrors('acepta_privacidad');
        $this->assertDatabaseCount('reclamos', 0);
        Mail::assertNothingQueued();
    }

    public function test_exige_datos_del_padre_tutor_si_es_menor_de_edad(): void
    {
        Mail::fake();

        $r = $this->post(route('libro-reclamaciones.store'), $this->datosReclamo(['es_menor_edad' => '1']));

        $r->assertSessionHasErrors(['nombre_padre_tutor', 'documento_padre_tutor']);
        $this->assertDatabaseCount('reclamos', 0);
    }

    public function test_guarda_los_datos_del_padre_tutor_si_es_menor_de_edad(): void
    {
        Mail::fake();

        $r = $this->post(route('libro-reclamaciones.store'), $this->datosReclamo([
            'es_menor_edad' => '1',
            'nombre_padre_tutor' => 'María Perez',
            'documento_padre_tutor' => '87654321',
        ]));

        $r->assertOk();
        $this->assertDatabaseHas('reclamos', [
            'es_menor_edad' => true,
            'nombre_padre_tutor' => 'María Perez',
        ]);
    }

    public function test_valida_dni_de_8_digitos(): void
    {
        Mail::fake();

        $r = $this->post(route('libro-reclamaciones.store'), $this->datosReclamo(['numero_documento_consumidor' => '123']));

        $r->assertSessionHasErrors('numero_documento_consumidor');
    }

    public function test_el_monto_es_opcional(): void
    {
        Mail::fake();

        $r = $this->post(route('libro-reclamaciones.store'), $this->datosReclamo(['monto' => '']));

        $r->assertOk();
        $this->assertDatabaseHas('reclamos', ['monto_centimos' => null]);
    }

    public function test_notifica_al_admin_si_esta_configurado(): void
    {
        config(['tienda.admin_email' => 'admin@enlix.pe']);
        Mail::fake();

        $this->post(route('libro-reclamaciones.store'), $this->datosReclamo());

        Mail::assertQueued(\App\Mail\ReclamoRegistradoAdmin::class, fn ($mail) => $mail->hasTo('admin@enlix.pe'));
    }

    public function test_no_notifica_al_admin_si_no_esta_configurado(): void
    {
        config(['tienda.admin_email' => null]);
        Mail::fake();

        $this->post(route('libro-reclamaciones.store'), $this->datosReclamo());

        Mail::assertNotQueued(\App\Mail\ReclamoRegistradoAdmin::class);
    }

    public function test_limite_de_envios_por_ip(): void
    {
        Mail::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('libro-reclamaciones.store'), $this->datosReclamo(['correo_consumidor' => "cliente{$i}@example.com"]))
                ->assertOk();
        }

        $this->post(route('libro-reclamaciones.store'), $this->datosReclamo(['correo_consumidor' => 'cliente6@example.com']))
            ->assertStatus(429);
    }
}
