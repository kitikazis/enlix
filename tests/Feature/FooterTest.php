<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class FooterTest extends TestCase
{
    public function test_el_enlace_al_admin_no_aparece_en_produccion(): void
    {
        $this->app['env'] = 'production';

        $this->get('/')
            ->assertOk()
            ->assertSee('Soluciones tecnológicas para empresas en el Perú.')
            ->assertDontSee('href="'.route('admin.dashboard').'"', false);
    }

    public function test_el_enlace_al_admin_aparece_fuera_de_produccion(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('admin.dashboard').'"', false);
    }
}
