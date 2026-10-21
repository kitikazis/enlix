<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Closure;
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
            'nombre' => ['required', 'string', 'max:100', function (string $attribute, mixed $valor, Closure $fail): void {
                $existente = Categoria::whereRaw('LOWER(nombre) = ?', [mb_strtolower(trim((string) $valor))])->first();

                if ($existente) {
                    $fail("Ya existe la categoría «{$existente->nombre}».");
                }
            }],
        ], [
            'nombre.required' => 'Escribe un nombre.',
            'nombre.max' => 'El nombre no puede pasar de 100 caracteres.',
        ]);

        $categoria = Categoria::create([
            'nombre' => trim($datos['nombre']),
            'slug' => $this->slugUnico($datos['nombre']),
        ]);

        return response()->json(['id' => $categoria->id, 'nombre' => $categoria->nombre], 201);
    }

    /** "!!!" o solo emojis dan Str::slug() vacío: se cae a "categoria". */
    private function slugUnico(string $nombre): string
    {
        $base = Str::slug($nombre) ?: 'categoria';
        $slug = $base;
        $sufijo = 1;

        while (Categoria::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$sufijo);
        }

        return $slug;
    }
}
