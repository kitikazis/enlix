<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Oferta del día de /productos: se publica desde el modal de producto del
 * admin (pestaña "Oferta del día") por 24 horas reales.
 */
class OfertaDelDiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function categoria(): Categoria
    {
        return Categoria::create(['nombre' => 'Laptops', 'slug' => 'laptops-'.uniqid()]);
    }

    /** Payload válido completo para el modal; los tests solo pisan lo que les importa. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'categoria_id' => $this->categoria()->id,
            'referencia' => 'REF-'.uniqid(),
            'nombre' => 'NB ROG Zephyrus G16',
            'descripcion' => '<p>Pantalla envolvente y rendimiento para tus proyectos.</p>',
            'especificaciones' => json_encode([
                ['clave' => 'Pantalla', 'valor' => '2.5K WQXGA OLED'],
                ['clave' => 'Procesador', 'valor' => 'Core Ultra 9'],
            ]),
            'precio' => '7499.00',
            'precio_comparacion' => '8999.00',
            'stock' => 5,
            'activo' => '1',
            'oferta' => '1',
            'oferta_titular' => 'La potencia que buscas. Hoy es el momento.',
            'oferta_bajada' => 'Descubre el ROG Zephyrus G16 por tiempo limitado.',
            'oferta_beneficios' => json_encode([
                ['titulo' => 'Imagen que impresiona', 'texto' => 'OLED 2.5K.'],
                ['titulo' => '', 'texto' => ''],
                ['titulo' => 'Espacio para avanzar', 'texto' => ''],
            ]),
            'imagen' => UploadedFile::fake()->image('foto.jpg', 400, 400),
        ], $overrides);
    }

    private function crear(array $overrides = []): Producto
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.productos.store'), $this->payload($overrides), ['Accept' => 'application/json'])
            ->assertCreated();

        return Producto::latest('id')->firstOrFail();
    }

    private function actualizar(Producto $producto, array $overrides = [])
    {
        return $this->actingAs(User::factory()->create())->post(
            route('admin.productos.update', $producto),
            array_merge($this->payload(['referencia' => $producto->referencia]), ['_method' => 'PUT'], $overrides),
            ['Accept' => 'application/json']
        );
    }

    public function test_publicar_oferta_abre_una_ventana_de_24_horas(): void
    {
        $this->freezeSecond();

        $producto = $this->crear();

        $this->assertTrue($producto->oferta_inicia_en->equalTo(now()));
        $this->assertTrue($producto->oferta_termina_en->equalTo(now()->addHours(24)));
        $this->assertSame(899900, $producto->precio_comparacion_centimos);
        $this->assertSame('La potencia que buscas. Hoy es el momento.', $producto->oferta_titular);
        // La casilla vacía se descarta; la que solo tiene título se guarda.
        $this->assertSame([
            ['titulo' => 'Imagen que impresiona', 'texto' => 'OLED 2.5K.'],
            ['titulo' => 'Espacio para avanzar', 'texto' => ''],
        ], $producto->oferta_beneficios);
    }

    public function test_publicar_otra_oferta_quita_la_anterior_y_devuelve_su_fila(): void
    {
        $anterior = $this->crear();

        $respuesta = $this->actingAs(User::factory()->create())
            ->post(route('admin.productos.store'), $this->payload(['nombre' => 'Monitor 4K']), ['Accept' => 'application/json'])
            ->assertCreated();

        $this->assertNull($anterior->fresh()->oferta_termina_en);
        $this->assertCount(1, $respuesta->json('filas_extra'));
        $this->assertStringContainsString('fila-producto-'.$anterior->id, $respuesta->json('filas_extra.0'));
        $this->assertSame('Monitor 4K', Producto::ofertaDelDia()->nombre);
    }

    public function test_editar_una_oferta_vigente_no_reinicia_el_contador(): void
    {
        $producto = $this->crear();
        $terminaba = $producto->oferta_termina_en;

        $this->travel(3)->hours();
        $this->actualizar($producto, ['oferta_titular' => 'Titular corregido'])->assertOk();

        $producto->refresh();
        $this->assertTrue($producto->oferta_termina_en->equalTo($terminaba));
        $this->assertSame('Titular corregido', $producto->oferta_titular);
    }

    public function test_reiniciar_vuelve_a_dar_24_horas_desde_ahora(): void
    {
        $producto = $this->crear();

        $this->travel(3)->hours();
        $this->freezeSecond();
        $this->actualizar($producto, ['oferta_reiniciar' => '1'])->assertOk();

        $this->assertTrue($producto->fresh()->oferta_termina_en->equalTo(now()->addHours(24)));
    }

    public function test_desmarcar_la_oferta_la_quita_pero_conserva_los_textos(): void
    {
        $producto = $this->crear();

        $this->actualizar($producto, ['oferta' => '0'])->assertOk();

        $producto->refresh();
        $this->assertNull($producto->oferta_termina_en);
        $this->assertSame('La potencia que buscas. Hoy es el momento.', $producto->oferta_titular);
        $this->assertNull(Producto::ofertaDelDia());
    }

    public function test_guardar_sin_el_campo_oferta_no_toca_la_oferta(): void
    {
        $producto = $this->crear();
        $payload = $this->payload(['referencia' => $producto->referencia]);
        unset($payload['oferta'], $payload['oferta_titular'], $payload['oferta_bajada'], $payload['oferta_beneficios']);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.productos.update', $producto), $payload + ['_method' => 'PUT'], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertTrue($producto->fresh()->tieneOfertaVigente());
    }

    public function test_la_oferta_exige_titular(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.productos.store'), $this->payload(['oferta_titular' => '']), ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['oferta_titular' => 'Escribe el titular de la oferta.']);
    }

    public function test_un_producto_oculto_no_puede_ser_la_oferta(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.productos.store'), $this->payload(['activo' => '0']), ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['oferta' => 'Visible en la tienda']);
    }

    public function test_el_precio_regular_debe_ser_mayor_al_precio(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.productos.store'), $this->payload(['precio_comparacion' => '7499.00']), ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['precio_comparacion' => 'mayor al precio de venta']);
    }

    public function test_beneficios_maximo_tres_y_sin_texto_huerfano(): void
    {
        $user = User::factory()->create();
        $cuatro = json_encode(array_fill(0, 4, ['titulo' => 'A', 'texto' => 'B']));

        $this->actingAs($user)
            ->post(route('admin.productos.store'), $this->payload(['oferta_beneficios' => $cuatro]), ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['oferta_beneficios' => 'Máximo 3 beneficios.']);

        $this->actingAs($user)
            ->post(route('admin.productos.store'), $this->payload(['oferta_beneficios' => json_encode([['titulo' => '', 'texto' => 'Sin título']])]), ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['oferta_beneficios' => 'Beneficio 1: falta el título.']);
    }

    public function test_productos_muestra_la_oferta_vigente_con_su_contador(): void
    {
        $this->freezeSecond();
        $producto = $this->crear();

        $this->get(route('productos'))
            ->assertOk()
            ->assertSee('aprovecha ahora!')
            ->assertSee('La potencia que buscas. Hoy es el momento.')
            ->assertSee('Oferta especial · 24 horas')
            ->assertSee('data-segundos="86400"', false)
            ->assertSee('S/ 8,999.00')
            ->assertSee('-17%')
            ->assertSee('2.5K WQXGA OLED')
            ->assertSee('Imagen que impresiona')
            ->assertSee('data-producto-id="'.$producto->id.'"', false)
            ->assertSee('Más productos');
    }

    public function test_la_ficha_tecnica_muestra_todas_las_especificaciones_y_permite_comprar(): void
    {
        $producto = $this->crear(['especificaciones' => json_encode([
            ['clave' => 'Pantalla', 'valor' => '10.95 pulgadas'],
            ['clave' => 'Batería', 'valor' => '8000 mAh'],
            ['clave' => 'Memoria RAM', 'valor' => '8 GB'],
            ['clave' => 'Almacenamiento', 'valor' => '128 GB'],
            ['clave' => 'Conectividad', 'valor' => '4G LTE'],
        ])]);

        $html = $this->get(route('productos'))
            ->assertOk()
            ->assertSee('Ficha técnica')
            ->assertSee('4G LTE')
            ->assertDontSee('Ver todas las especificaciones')
            ->getContent();

        $this->assertSame(2, substr_count($html, 'data-oferta-comprar data-producto-id="'.$producto->id.'"'));
    }

    public function test_la_oferta_no_se_repite_en_la_grilla_del_catalogo(): void
    {
        $this->crear(['nombre' => 'Producto unico en oferta']);

        $html = $this->get(route('productos'))->getContent();

        $this->assertSame(0, substr_count($html, 'class="prod-card-title">Producto unico en oferta'));
    }

    public function test_la_oferta_vencida_ya_no_se_muestra(): void
    {
        $this->crear();

        $this->travel(24)->hours();
        $this->travel(1)->seconds();

        $this->assertNull(Producto::ofertaDelDia());
        $this->get(route('productos'))
            ->assertOk()
            ->assertDontSee('La potencia que buscas')
            ->assertSee('Nuestros productos');
    }

    public function test_la_oferta_de_un_producto_desactivado_no_se_muestra(): void
    {
        $producto = $this->crear();

        $this->actingAs(User::factory()->create())
            ->patchJson(route('admin.productos.alternar-activo', $producto))
            ->assertOk();

        $this->get(route('productos'))->assertDontSee('La potencia que buscas');
    }

    public function test_sin_oferta_productos_se_ve_como_siempre(): void
    {
        $this->get(route('productos'))
            ->assertOk()
            ->assertSee('Nuestros productos')
            ->assertDontSee('class="oferta-hero"', false)
            ->assertSee('android-chrome-192x192.png', false)
            ->assertDontSee('"@type":"Product"', false);
    }

    public function test_al_compartir_productos_la_vista_previa_es_la_foto_de_la_oferta(): void
    {
        $producto = $this->crear();

        $this->get(route('productos'))
            ->assertSee('<meta property="og:image" content="'.e($producto->imagen_url).'">', false)
            ->assertSee('<meta property="og:image:alt" content="NB ROG Zephyrus G16">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<meta property="og:description" content="Descubre el ROG Zephyrus G16 por tiempo limitado.">', false);
    }

    public function test_la_oferta_tiene_datos_estructurados_de_producto_para_google(): void
    {
        $this->freezeSecond();
        $this->crear();

        $html = $this->get(route('productos'))->getContent();
        preg_match_all('#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $html, $bloques);
        $producto = collect($bloques[1])->map(fn ($json) => json_decode($json, true))->firstWhere('@type', 'Product');

        $this->assertNotNull($producto);
        $this->assertSame('NB ROG Zephyrus G16', $producto['name']);
        $this->assertSame('PEN', $producto['offers']['priceCurrency']);
        $this->assertSame('7499.00', $producto['offers']['price']);
        $this->assertSame('https://schema.org/InStock', $producto['offers']['availability']);
        $this->assertSame(now()->addDay()->setTimezone('America/Lima')->toDateString(), $producto['offers']['priceValidUntil']);
    }
}
