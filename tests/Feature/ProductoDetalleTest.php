<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Support\Producto as Catalogo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Página de detalle de producto (/productos/{slug}): se abre desde las
 * tarjetas del catálogo y permite comprar igual que la oferta del día.
 */
class ProductoDetalleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function producto(array $overrides = []): Producto
    {
        return Producto::create(array_merge([
            'slug' => 'gpu-test-'.uniqid(),
            'referencia' => 'REF-'.uniqid(),
            'nombre' => 'GPU de prueba',
            'descripcion' => '<p>Descripción completa de la GPU.</p>',
            'precio_centimos' => 150000,
            'stock' => 5,
            'activo' => true,
        ], $overrides));
    }

    private function jsonLd(string $html, string $tipo): ?array
    {
        preg_match_all('#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $html, $m);

        foreach ($m[1] as $json) {
            $datos = json_decode($json, true);
            if (($datos['@type'] ?? null) === $tipo) {
                return $datos;
            }
        }

        return null;
    }

    public function test_muestra_la_informacion_completa_del_producto(): void
    {
        $producto = $this->producto([
            'marca_id' => Marca::create(['nombre' => 'ASUS', 'slug' => 'asus'])->id,
            'categoria_id' => Categoria::create(['nombre' => 'Laptops', 'slug' => 'laptops'])->id,
            'descripcion_corta' => 'Potencia para tus proyectos.',
            'precio_comparacion_centimos' => 200000,
            'meses_garantia' => 12,
            'especificaciones' => [['clave' => 'Memoria RAM', 'valor' => '32 GB']],
            'features' => ['Pantalla OLED'],
        ]);

        $this->get(route('productos.show', $producto->slug))
            ->assertOk()
            ->assertSee('<h1 class="pd-nombre">GPU de prueba</h1>', false)
            ->assertSee('<p class="pd-marca">ASUS</p>', false)
            ->assertSee('Código: '.$producto->referencia)
            ->assertSee('1,500.00')
            ->assertSee('Precio regular S/ 2,000.00')
            ->assertSee('-25%')
            ->assertSee('Disponible 5 unidades')
            ->assertSee('Descripción completa de la GPU.')
            ->assertSee('Memoria RAM')
            ->assertSee('32 GB')
            ->assertSee('Pantalla OLED')
            ->assertSee('Garantía de 12 meses')
            ->assertSee('data-oferta-comprar', false)
            ->assertSee('btn-agregar-carrito', false)
            ->assertSee('data-max="5"', false);
    }

    public function test_sin_puntos_propios_usa_las_primeras_especificaciones(): void
    {
        $producto = $this->producto([
            'especificaciones' => [
                ['clave' => 'Memoria RAM', 'valor' => '32 GB'],
                ['clave' => 'A', 'valor' => '1'], ['clave' => 'B', 'valor' => '2'],
                ['clave' => 'C', 'valor' => '3'], ['clave' => 'D', 'valor' => '4'],
                ['clave' => 'Sexta', 'valor' => '6'],
            ],
        ]);

        $this->get(route('productos.show', $producto->slug))
            ->assertSee('<li>Memoria RAM: 32 GB</li>', false)
            ->assertDontSee('<li>Sexta: 6</li>', false);
    }

    public function test_mas_de_10_unidades_no_revela_el_stock_exacto(): void
    {
        $producto = $this->producto(['stock' => 37]);

        $this->get(route('productos.show', $producto->slug))
            ->assertSee('Disponible más de 10 unidades')
            ->assertDontSee('37 unidades');
    }

    public function test_el_selector_de_cantidad_no_pasa_de_99(): void
    {
        $producto = $this->producto(['stock' => 500]);

        $this->get(route('productos.show', $producto->slug))
            ->assertSee('data-max="99"', false);
    }

    public function test_con_varias_fotos_muestra_la_galeria_en_orden(): void
    {
        $producto = $this->producto(['imagen' => 'productos/principal.webp']);
        $producto->imagenes()->create(['ruta' => 'productos/segunda.webp', 'orden' => 2]);
        $producto->imagenes()->create(['ruta' => 'productos/primera-extra.webp', 'orden' => 1]);

        $respuesta = $this->get(route('productos.show', $producto->slug))
            ->assertSee('Ver foto 3 de 3')
            ->assertSee('data-galeria-siguiente', false);

        $respuesta->assertSeeInOrder([
            'storage/productos/principal.webp',
            'data-foto="'.$producto->imagenes[0]->url,
            'data-foto="'.$producto->imagenes[1]->url,
        ], false);
    }

    public function test_con_una_sola_foto_no_muestra_flechas_ni_miniaturas(): void
    {
        $producto = $this->producto();

        $this->get(route('productos.show', $producto->slug))
            ->assertDontSee('class="pd-miniaturas"', false)
            ->assertDontSee('class="pd-flecha', false);
    }

    public function test_sin_stock_no_deja_comprar(): void
    {
        $producto = $this->producto(['stock' => 0]);

        $this->get(route('productos.show', $producto->slug))
            ->assertOk()
            ->assertSee('Agotado')
            ->assertDontSee('data-oferta-comprar data-producto-id', false)
            ->assertDontSee('pd-cta btn-agregar-carrito', false);
    }

    public function test_avisa_cuando_quedan_pocas_unidades(): void
    {
        $producto = $this->producto(['stock' => 2, 'umbral_stock_bajo' => 5]);

        $this->get(route('productos.show', $producto->slug))
            ->assertSee('¡Últimas 2 unidades!');
    }

    public function test_producto_inactivo_eliminado_o_inexistente_da_404(): void
    {
        $inactivo = $this->producto(['activo' => false]);
        $eliminado = $this->producto();
        $eliminado->delete();

        $this->get(route('productos.show', $inactivo->slug))->assertNotFound();
        $this->get(route('productos.show', $eliminado->slug))->assertNotFound();
        $this->get('/productos/no-existe')->assertNotFound();
    }

    public function test_las_tarjetas_del_catalogo_llevan_al_detalle(): void
    {
        $producto = $this->producto();

        $this->get(route('productos'))
            ->assertSee('href="'.route('productos.show', $producto->slug).'" class="stretched-link"', false);
    }

    public function test_relacionados_prioriza_la_misma_categoria_y_excluye_al_producto(): void
    {
        $laptops = Categoria::create(['nombre' => 'Laptops', 'slug' => 'laptops']);
        $impresoras = Categoria::create(['nombre' => 'Impresoras', 'slug' => 'impresoras']);

        $otraCategoria = $this->producto(['nombre' => 'Impresora', 'categoria_id' => $impresoras->id, 'orden' => 1]);
        $mismaCategoria = $this->producto(['nombre' => 'Laptop B', 'categoria_id' => $laptops->id, 'orden' => 2]);
        $this->producto(['nombre' => 'Oculto', 'categoria_id' => $laptops->id, 'activo' => false]);
        $producto = $this->producto(['nombre' => 'Laptop A', 'categoria_id' => $laptops->id, 'orden' => 3]);

        $relacionados = array_column(Catalogo::relacionados($producto, 2), 'id');

        $this->assertSame([$mismaCategoria->id, $otraCategoria->id], $relacionados);
    }

    public function test_incluye_datos_estructurados_y_la_foto_al_compartir(): void
    {
        $producto = $this->producto(['imagen' => 'productos/gpu.webp']);

        $respuesta = $this->get(route('productos.show', $producto->slug));

        $product = $this->jsonLd($respuesta->getContent(), 'Product');
        $this->assertSame('GPU de prueba', $product['name']);
        $this->assertSame('1500.00', $product['offers']['price']);
        $this->assertSame(route('productos.show', $producto->slug), $product['offers']['url']);
        $this->assertArrayNotHasKey('priceValidUntil', $product['offers']);
        $this->assertNotNull($this->jsonLd($respuesta->getContent(), 'BreadcrumbList'));

        $respuesta->assertSee('<meta property="og:image" content="'.$producto->imagen_url.'">', false)
            ->assertSee('<title>GPU de prueba - Enlix</title>', false);
    }

    public function test_el_sitemap_lista_solo_productos_activos(): void
    {
        $activo = $this->producto();
        $inactivo = $this->producto(['activo' => false]);

        $this->get(route('sitemap'))
            ->assertSee('<loc>'.route('productos.show', $activo->slug).'</loc>', false)
            ->assertDontSee($inactivo->slug);
    }
}
