<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PaginaLegal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaginasLegalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_es_redirigido_a_login(): void
    {
        $this->get(route('admin.paginas-legales.index'))->assertRedirect('/admin/login');
    }

    public function test_lista_las_paginas_legales(): void
    {
        $user = User::factory()->create();

        $r = $this->actingAs($user)->get(route('admin.paginas-legales.index'));

        $r->assertOk();
        $r->assertSee('Términos y condiciones');
        $r->assertSee('Política de privacidad');
    }

    public function test_edita_el_contenido_de_una_pagina(): void
    {
        $user = User::factory()->create();
        $pagina = PaginaLegal::obtener(PaginaLegal::SLUG_TERMINOS);

        $r = $this->actingAs($user)->put(route('admin.paginas-legales.update', $pagina), [
            'titulo' => 'Términos y condiciones',
            'contenido' => '<h2>Prueba</h2><p>Nuevo contenido.</p>',
            'meta_title' => 'Título SEO',
            'meta_description' => 'Descripción SEO',
        ]);

        $r->assertRedirect(route('admin.paginas-legales.index'));
        $pagina->refresh();
        $this->assertStringContainsString('Nuevo contenido.', $pagina->contenido);
        $this->assertSame('Título SEO', $pagina->meta_title);
    }

    public function test_sanea_html_no_permitido_en_el_contenido(): void
    {
        $user = User::factory()->create();
        $pagina = PaginaLegal::obtener(PaginaLegal::SLUG_PRIVACIDAD);

        $this->actingAs($user)->put(route('admin.paginas-legales.update', $pagina), [
            'titulo' => $pagina->titulo,
            'contenido' => '<script>alert(1)</script><p>Texto seguro</p>',
        ]);

        $pagina->refresh();
        $this->assertStringNotContainsString('<script>', $pagina->contenido);
        $this->assertStringContainsString('Texto seguro', $pagina->contenido);
    }

    public function test_el_cambio_se_ve_de_inmediato_en_la_pagina_publica(): void
    {
        $user = User::factory()->create();
        $pagina = PaginaLegal::obtener(PaginaLegal::SLUG_TERMINOS);

        $this->actingAs($user)->put(route('admin.paginas-legales.update', $pagina), [
            'titulo' => 'Términos y condiciones',
            'contenido' => '<h2>Sección</h2><p>Contenido recién editado.</p>',
        ]);

        $this->get('/terminos-y-condiciones')->assertSee('Contenido recién editado.');
    }
}
