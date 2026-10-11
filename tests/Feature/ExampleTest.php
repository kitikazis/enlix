<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_el_footer_tiene_la_columna_legal_en_todas_las_paginas(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Legal');
        $response->assertSee(route('terminos'), false);
        $response->assertSee(route('politica-privacidad'), false);
        $response->assertSee(route('libro-reclamaciones.index'), false);
    }
}
