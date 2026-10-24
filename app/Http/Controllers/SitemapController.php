<?php

namespace App\Http\Controllers;

use App\Support\Catalogo;
use Illuminate\Http\Response;

/**
 * sitemap.xml para Google Search Console: páginas públicas indexables.
 * Carrito, checkout y admin quedan fuera (ver también public/robots.txt).
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ['loc' => route('inicio'), 'priority' => '1.0'],
            ['loc' => route('nosotros'), 'priority' => '0.7'],
            ['loc' => route('contacto'), 'priority' => '0.8'],
            ['loc' => route('productos'), 'priority' => '0.6'],
        ];

        foreach (array_keys(Catalogo::items()) as $slug) {
            $urls[] = ['loc' => route('servicio.show', $slug), 'priority' => '0.9'];
        }

        $urls[] = ['loc' => route('libro-reclamaciones.index'), 'priority' => '0.3'];
        $urls[] = ['loc' => route('terminos'), 'priority' => '0.2'];
        $urls[] = ['loc' => route('politica-privacidad'), 'priority' => '0.2'];

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
