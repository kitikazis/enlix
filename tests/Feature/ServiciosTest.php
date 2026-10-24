<?php

namespace Tests\Feature;

use App\Support\Catalogo;
use Tests\TestCase;

class ServiciosTest extends TestCase
{
    public function test_el_catalogo_tiene_nueve_servicios_en_tres_grupos(): void
    {
        $this->assertCount(9, Catalogo::items());
        $this->assertSame(
            ['Mantenimiento y reparación', 'Configuración y gestión TI', 'Venta de equipos y suministros'],
            array_column(array_values(Catalogo::grupos()), 'label')
        );
    }

    public function test_cada_servicio_tiene_su_pagina(): void
    {
        foreach (Catalogo::items() as $slug => $svc) {
            $this->get(route('servicio.show', $slug))
                ->assertOk()
                ->assertSee($svc['title'])
                ->assertSee('Cobertura a nivel nacional');
        }
    }

    public function test_la_pagina_de_servicio_muestra_los_equipos_que_atiende(): void
    {
        $this->get(route('servicio.show', 'mantenimiento-preventivo'))
            ->assertOk()
            ->assertSee('Equipos que atendemos')
            ->assertSee('Mac Mini')
            ->assertSee('Cambio de pasta térmica');
    }

    public function test_un_servicio_retirado_redirige_301_a_su_reemplazo(): void
    {
        $this->get('/servicio-distribucion-equipos')
            ->assertStatus(301)
            ->assertRedirect(route('servicio.show', 'venta-equipos'));
    }

    public function test_un_servicio_retirado_sin_reemplazo_redirige_a_los_servicios_del_inicio(): void
    {
        $this->get('/servicio-cctv')
            ->assertStatus(301)
            ->assertRedirect(route('inicio').'#servicios');
    }

    public function test_todas_las_redirecciones_apuntan_a_servicios_existentes(): void
    {
        foreach (config('servicios.redirecciones') as $viejo => $nuevo) {
            $this->assertNull(Catalogo::find($viejo), "El slug retirado {$viejo} sigue en el catálogo");

            if ($nuevo !== null) {
                $this->assertNotNull(Catalogo::find($nuevo), "La redirección de {$viejo} apunta a un servicio inexistente");
            }
        }
    }

    public function test_un_servicio_desconocido_devuelve_404(): void
    {
        $this->get('/servicio-no-existe')->assertNotFound();
    }

    public function test_el_inicio_lista_los_nueve_servicios(): void
    {
        $respuesta = $this->get(route('inicio'))->assertOk();

        foreach (Catalogo::items() as $slug => $svc) {
            $respuesta->assertSee(route('servicio.show', $slug), false);
        }

        $respuesta->assertDontSee(route('servicio.show', 'cctv'), false);
    }

    public function test_nosotros_muestra_mision_vision_y_los_diez_valores(): void
    {
        $respuesta = $this->get(route('nosotros'))
            ->assertOk()
            ->assertSee('Convertirnos en el aliado tecnológico de confianza de las empresas del Perú')
            ->assertSee('Brindar soluciones tecnológicas integrales');

        $this->assertSame(10, substr_count($respuesta->getContent(), 'class="valor-item"'));
    }
}
