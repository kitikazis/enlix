<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Páginas institucionales de Enlix (inicio, nosotros, contacto).
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
}
