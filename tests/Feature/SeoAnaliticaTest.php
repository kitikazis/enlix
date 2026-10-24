<?php

namespace Tests\Feature;

use App\Support\Catalogo;
use Tests\TestCase;

class SeoAnaliticaTest extends TestCase
{
    public function test_el_sitemap_lista_las_paginas_publicas_y_todos_los_servicios(): void
    {
        $respuesta = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.route('inicio').'</loc>', false)
            ->assertSee('<loc>'.route('nosotros').'</loc>', false);

        foreach (array_keys(Catalogo::items()) as $slug) {
            $respuesta->assertSee('<loc>'.route('servicio.show', $slug).'</loc>', false);
        }

        $respuesta->assertDontSee('/carrito', false)->assertDontSee('/admin', false);

        $xml = simplexml_load_string($respuesta->getContent());
        $this->assertNotFalse($xml, 'El sitemap no es XML válido');
        $this->assertCount(7 + count(Catalogo::items()), $xml->url);
    }

    public function test_robots_apunta_al_sitemap_y_excluye_admin_carrito_y_checkout(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap: https://enlix.pe/sitemap.xml', $robots);
        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Disallow: /carrito', $robots);
        $this->assertStringContainsString('Disallow: /checkout', $robots);
    }

    public function test_el_inicio_publica_datos_estructurados_de_la_empresa(): void
    {
        $html = $this->get(route('inicio'))->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.route('inicio').'">', $html);

        $empresa = $this->jsonLd($html, 'ProfessionalService');
        $this->assertSame('Enlix', $empresa['name']);
        $this->assertSame('+51963885176', $empresa['telephone']);
        $this->assertCount(count(Catalogo::items()), $empresa['hasOfferCatalog']['itemListElement']);
    }

    public function test_la_pagina_de_servicio_publica_datos_estructurados_del_servicio(): void
    {
        $html = $this->get(route('servicio.show', 'mantenimiento-preventivo'))->assertOk()->getContent();

        $servicio = $this->jsonLd($html, 'Service');
        $this->assertSame('Mantenimiento preventivo', $servicio['name']);
        $this->assertSame(route('inicio').'#empresa', $servicio['provider']['@id']);
        $this->assertNotNull($this->jsonLd($html, 'BreadcrumbList'));
        $this->assertStringContainsString('<meta property="og:image" content="https://images.unsplash.com/', $html);
    }

    public function test_sin_id_de_analytics_no_hay_aviso_de_cookies_ni_google_en_la_csp(): void
    {
        config(['services.google.analytics_id' => null]);

        $respuesta = $this->get(route('inicio'))->assertOk()
            ->assertDontSee('id="cookies-aviso"', false)
            ->assertDontSee('cookies.js', false);

        $this->assertStringNotContainsString('googletagmanager', $respuesta->headers->get('Content-Security-Policy'));
    }

    public function test_con_id_de_analytics_se_muestra_el_aviso_y_la_csp_permite_google(): void
    {
        config(['services.google.analytics_id' => 'G-TEST123']);

        $respuesta = $this->get(route('inicio'))->assertOk()
            ->assertSee('data-ga-id="G-TEST123"', false)
            ->assertSee('cookies.js', false)
            ->assertSee('Preferencias de cookies');

        // gtag.js no se incluye en el HTML: solo lo carga cookies.js tras aceptar.
        $respuesta->assertDontSee('googletagmanager.com/gtag/js', false);

        $csp = $respuesta->headers->get('Content-Security-Policy');
        $this->assertMatchesRegularExpression('/script-src [^;]*\*\.googletagmanager\.com/', $csp);
        $this->assertMatchesRegularExpression('/connect-src [^;]*\*\.google-analytics\.com/', $csp);
    }

    public function test_la_verificacion_de_search_console_sale_solo_si_esta_configurada(): void
    {
        config(['services.google.site_verification' => null]);
        $this->get(route('inicio'))->assertDontSee('google-site-verification', false);

        config(['services.google.site_verification' => 'abc123']);
        $this->get(route('inicio'))->assertSee('<meta name="google-site-verification" content="abc123">', false);
    }

    /** Devuelve el bloque JSON-LD del @type dado, o null. */
    private function jsonLd(string $html, string $tipo): ?array
    {
        preg_match_all('#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $html, $m);

        foreach ($m[1] as $json) {
            $datos = json_decode($json, true);
            $this->assertIsArray($datos, 'JSON-LD inválido');
            if (($datos['@type'] ?? null) === $tipo) {
                return $datos;
            }
        }

        return null;
    }
}
