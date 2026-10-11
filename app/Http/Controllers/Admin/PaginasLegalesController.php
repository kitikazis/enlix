<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaginaLegal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Edicion del contenido de /terminos-y-condiciones y /politica-de-privacidad
 * sin tocar codigo (ver PaginaLegal::obtener(), que crea el borrador
 * placeholder la primera vez que se pide un slug).
 */
class PaginasLegalesController extends Controller
{
    public function index(): View
    {
        return view('admin.paginas-legales.index', [
            'titulo' => 'Páginas legales - Enlix Admin',
            'paginas' => [
                PaginaLegal::obtener(PaginaLegal::SLUG_TERMINOS),
                PaginaLegal::obtener(PaginaLegal::SLUG_PRIVACIDAD),
            ],
        ]);
    }

    public function edit(PaginaLegal $pagina): View
    {
        return view('admin.paginas-legales.edit', [
            'titulo' => 'Editar '.$pagina->titulo.' - Enlix Admin',
            'pagina' => $pagina,
        ]);
    }

    public function update(Request $request, PaginaLegal $pagina): RedirectResponse
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            // El saneado real (tags permitidos) lo hace el mutator
            // PaginaLegal::contenido() con Purifier, no esta validacion.
            'contenido' => ['required', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
        ]);

        $pagina->update($datos);

        return redirect()->route('admin.paginas-legales.index')->with('status', 'Página actualizada.');
    }
}
