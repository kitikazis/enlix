<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Solo la creación rápida de categorías desde el modal de "Nuevo producto"
 * (botón "+" junto al select de Categoría). El resto del CRUD de
 * categorías no existe todavía: no hay página de listado ni edición.
 */
class CategoriasController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
        ]);

        $categoria = Categoria::create([
            'nombre' => $datos['nombre'],
            'slug' => $this->slugUnico($datos['nombre']),
        ]);

        return response()->json(['id' => $categoria->id, 'nombre' => $categoria->nombre], 201);
    }

    private function slugUnico(string $nombre): string
    {
        $base = Str::slug($nombre);
        $slug = $base;
        $sufijo = 1;

        while (Categoria::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$sufijo);
        }

        return $slug;
    }
}
