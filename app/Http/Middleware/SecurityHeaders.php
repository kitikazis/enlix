<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad para todo el grupo 'web'.
 *
 * style-src usa 'unsafe-inline' A PROPOSITO (sin nonce): el sitio tiene
 * decenas de atributos style="" inline en varias vistas, y CSP no soporta
 * nonce en atributos de estilo (solo en bloques <style>). Si alguna vez se
 * agrega un nonce a style-src, los navegadores IGNORAN 'unsafe-inline' en
 * esa misma directiva y se rompe todo el sitio. No lo hagas.
 *
 * script-src si usa nonce: los <script> inline (productos.blade.php,
 * partials/sidebar-servicios.blade.php) deben llevar nonce="{{ $cspNonce }}".
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(16);
        $request->attributes->set('csp_nonce', $nonce);
        View::share('cspNonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('Content-Security-Policy', $this->csp($nonce));

        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function csp(string $nonce): string
    {
        $directivas = [
            "default-src 'self'",
            // El cliente Krypton (PopIn) reparte sus recursos entre varios
            // subdominios de micuentaweb.pe (static, secure, assets...), no
            // solo static.micuentaweb.pe: se usa comodin para no romper el
            // widget cada vez que Izipay agrega/cambia un subdominio interno.
            "script-src 'self' 'nonce-{$nonce}' cdn.jsdelivr.net *.micuentaweb.pe",
            "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com",
            "font-src 'self' fonts.gstatic.com",
            "img-src 'self' data: images.unsplash.com cdn.simpleicons.org *.micuentaweb.pe",
            "connect-src 'self' *.micuentaweb.pe",
            "frame-src 'self' *.micuentaweb.pe www.google.com",
            "frame-ancestors 'self'",
            "form-action 'self' *.micuentaweb.pe",
            "object-src 'none'",
            "base-uri 'self'",
        ];

        return implode('; ', $directivas);
    }
}
