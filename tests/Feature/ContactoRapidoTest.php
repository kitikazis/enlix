<?php

namespace Tests\Feature;

use App\Support\Contacto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactoRapidoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_enlace_de_whatsapp_lleva_el_mensaje_codificado(): void
    {
        $this->assertSame(
            'https://wa.me/51963885176?text=Hola%20Enlix%2C%20quiero%20cotizar%20el%20servicio%20de%20Mantenimiento%20preventivo.',
            Contacto::whatsappServicio('Mantenimiento preventivo')
        );
    }

    public function test_el_inicio_tiene_titular_de_propuesta_de_valor_y_cta_de_whatsapp(): void
    {
        $this->get(route('inicio'))
            ->assertOk()
            ->assertSee('<h1>Mantenimiento de computadoras e impresoras para empresas, en todo el Perú.</h1>', false)
            ->assertSee('Cotizar por WhatsApp')
            ->assertSee('class="contacto-barra-movil"', false);
    }

    public function test_la_pagina_de_servicio_cotiza_por_whatsapp_indicando_el_servicio(): void
    {
        $url = e(Contacto::whatsappServicio('Mantenimiento de impresoras'));

        $respuesta = $this->get(route('servicio.show', 'mantenimiento-impresoras'))->assertOk();

        // CTA del contenido, sidebar, botón flotante y barra móvil.
        $this->assertGreaterThanOrEqual(4, substr_count($respuesta->getContent(), $url));
    }

    public function test_la_tienda_no_muestra_la_barra_de_contacto_movil(): void
    {
        $this->get(route('productos'))
            ->assertOk()
            ->assertDontSee('class="contacto-barra-movil"', false);
    }
}
