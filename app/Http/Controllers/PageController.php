<?php

namespace App\Http\Controllers;

use App\Models\PaginaLegal;
use Illuminate\View\View;

/**
 * Páginas institucionales de Enlix (inicio, nosotros, contacto, legales).
 */
class PageController extends Controller
{
    public function inicio(): View
    {
        return view('inicio', [
            'titulo'  => 'Enlix - Soluciones tecnológicas para empresas',
            'current' => 'inicio',
        ]);
    }

    public function nosotros(): View
    {
        return view('nosotros', [
            'titulo'  => 'Nosotros - Enlix',
            'current' => 'nosotros',
        ]);
    }

    public function contacto(): View
    {
        return view('contacto', [
            'titulo'  => 'Contacto - Enlix',
            'current' => 'contacto',
        ]);
    }

    public function terminos(): View
    {
        $pagina = PaginaLegal::obtener(PaginaLegal::SLUG_TERMINOS);

        return view('legal.pagina', [
            'titulo' => $pagina->meta_title ?? $pagina->titulo.' - Enlix',
            'metaDescription' => $pagina->meta_description,
            'current' => 'terminos',
            'pagina' => $pagina,
        ]);
    }

    public function politicaDePrivacidad(): View
    {
        $pagina = PaginaLegal::obtener(PaginaLegal::SLUG_PRIVACIDAD);

        return view('legal.pagina', [
            'titulo' => $pagina->meta_title ?? $pagina->titulo.' - Enlix',
            'metaDescription' => $pagina->meta_description,
            'current' => 'privacidad',
            'pagina' => $pagina,
        ]);
    }
}
