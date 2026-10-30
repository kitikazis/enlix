<?php

namespace App\Http\Controllers;

use App\Support\Catalogo;
use App\Support\Producto;
use Illuminate\Http\Response;

/**
 * sitemap.xml para Google Search Console: páginas públicas indexables.
 * Carrito, checkout y admin quedan fuera (ver también public/robots.txt).
 *
 * El XML se arma aquí y no en una vista Blade a propósito: el hosting tiene
 * short_open_tag=On, y el "<?xml" de una plantilla se interpreta como
 * apertura de PHP (error 500 solo en producción).
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            [route('inicio'), '1.0'],
            [route('nosotros'), '0.7'],
            [route('contacto'), '0.8'],
            [route('productos'), '0.6'],
        ];

        foreach (array_keys(Catalogo::items()) as $slug) {
            $urls[] = [route('servicio.show', $slug), '0.9'];
        }

        foreach (array_keys(Producto::items()) as $slug) {
            $urls[] = [route('productos.show', $slug), '0.5'];
        }

        $urls[] = [route('libro-reclamaciones.index'), '0.3'];
        $urls[] = [route('terminos'), '0.2'];
        $urls[] = [route('politica-privacidad'), '0.2'];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as [$loc, $prioridad]) {
            $xml .= '  <url><loc>'.htmlspecialchars($loc, ENT_XML1).'</loc>'
                .'<priority>'.$prioridad.'</priority></url>'."\n";
        }

        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
