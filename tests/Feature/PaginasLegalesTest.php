<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PaginaLegal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginasLegalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_terminos_y_condiciones_crea_el_borrador_la_primera_vez(): void
    {
        $this->assertDatabaseCount('paginas_legales', 0);

        $r = $this->get('/terminos-y-condiciones');

        $r->assertOk();
        $r->assertSee('Términos y condiciones');
        $r->assertSee('[RAZÓN SOCIAL]');
        $this->assertDatabaseHas('paginas_legales', ['slug' => 'terminos-y-condiciones']);
    }

    public function test_terminos_viejo_redirige_al_nuevo(): void
    {
        $this->get('/terminos')->assertRedirect('/terminos-y-condiciones');
    }

    public function test_enlace_al_libro_de_reclamaciones_en_terminos(): void
    {
        $r = $this->get('/terminos-y-condiciones');

        $r->assertOk();
        $r->assertSee(route('libro-reclamaciones.index'), false);
    }

    public function test_politica_de_privacidad_crea_el_borrador_la_primera_vez(): void
    {
        $r = $this->get('/politica-de-privacidad');

        $r->assertOk();
        $r->assertSee('Política de privacidad');
        $r->assertSee('derechos ARCO', false);
        $this->assertDatabaseHas('paginas_legales', ['slug' => 'politica-de-privacidad']);
    }

    public function test_indice_de_secciones_enlaza_a_los_encabezados(): void
    {
        $r = $this->get('/terminos-y-condiciones');

        $r->assertOk();
        $r->assertSee('id="1-identificacion-del-proveedor"', false);
        $r->assertSee('href="#1-identificacion-del-proveedor"', false);
    }

    public function test_meta_description_propia_no_la_generica_del_sitio(): void
    {
        $r = $this->get('/terminos-y-condiciones');

        $r->assertOk();
        $r->assertDontSee('Enlix - Soluciones tecnológicas y servicios de TI para empresas. Equipos', false);
    }

    public function test_contenido_editado_desde_el_admin_se_ve_en_la_pagina_publica(): void
    {
        PaginaLegal::obtener(PaginaLegal::SLUG_TERMINOS)->update([
            'titulo' => 'Términos y condiciones',
            'contenido' => '<h2>Prueba</h2><p>Texto editado desde el admin.</p>',
        ]);

        $r = $this->get('/terminos-y-condiciones');

        $r->assertOk();
        $r->assertSee('Texto editado desde el admin.');
        $r->assertDontSee('[RAZÓN SOCIAL]');
    }
}
