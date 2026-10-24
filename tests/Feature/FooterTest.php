<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class FooterTest extends TestCase
{
    public function test_el_footer_no_muestra_enlace_al_admin(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Mantenimiento de equipos de cómputo e impresoras en todo el Perú.')
            ->assertDontSee('href="'.route('admin.dashboard').'"', false);
    }
}
