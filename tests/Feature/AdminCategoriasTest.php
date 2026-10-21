<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Creación rápida de categorías desde el botón "+" del modal de productos. */
class AdminCategoriasTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_ruta_exige_auth(): void
    {
        $ruta = Route::getRoutes()->getByName('admin.categorias.store');
        $this->assertContains('auth', $ruta->gatherMiddleware());
    }

    public function test_crea_una_categoria_rapida_desde_el_modal(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('admin.categorias.store'), [
            'nombre' => 'Monitores',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('categorias', ['nombre' => 'Monitores', 'slug' => 'monitores']);
    }

    public function test_exige_nombre_con_mensaje_en_espanol(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('admin.categorias.store'), [])
            ->assertJsonValidationErrors(['nombre' => 'Escribe un nombre.']);
    }

    public function test_no_permite_categorias_repetidas_sin_importar_mayusculas(): void
    {
        $user = User::factory()->create();
        Categoria::create(['nombre' => 'Monitores', 'slug' => 'monitores']);

        foreach (['Monitores', 'MONITORES', '  monitores '] as $nombre) {
            $this->actingAs($user)->postJson(route('admin.categorias.store'), ['nombre' => $nombre])
                ->assertJsonValidationErrors(['nombre' => 'Ya existe la categoría «Monitores».']);
        }

        $this->assertSame(1, Categoria::whereRaw('LOWER(nombre) = ?', ['monitores'])->count());
    }

    public function test_nombres_distintos_con_el_mismo_slug_reciben_sufijo(): void
    {
        $user = User::factory()->create();
        Categoria::create(['nombre' => 'Monitores', 'slug' => 'monitores']);

        $this->actingAs($user)->postJson(route('admin.categorias.store'), ['nombre' => 'Monitores!'])
            ->assertCreated();

        $this->assertDatabaseHas('categorias', ['nombre' => 'Monitores!', 'slug' => 'monitores-2']);
    }

    public function test_un_nombre_sin_letras_no_deja_el_slug_vacio(): void
    {
        $this->actingAs(User::factory()->create())->postJson(route('admin.categorias.store'), ['nombre' => '!!!'])
            ->assertCreated();

        $this->assertDatabaseHas('categorias', ['nombre' => '!!!', 'slug' => 'categoria']);
    }
}
