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

    public function test_exige_nombre(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('admin.categorias.store'), [])
            ->assertJsonValidationErrors('nombre');
    }

    public function test_genera_slugs_distintos_para_nombres_repetidos(): void
    {
        $user = User::factory()->create();
        Categoria::create(['nombre' => 'Monitores', 'slug' => 'monitores']);

        $this->actingAs($user)->postJson(route('admin.categorias.store'), ['nombre' => 'Monitores'])
            ->assertCreated();

        $this->assertDatabaseHas('categorias', ['nombre' => 'Monitores', 'slug' => 'monitores-2']);
    }
}
