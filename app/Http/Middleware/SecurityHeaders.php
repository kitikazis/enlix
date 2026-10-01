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
 *
 * 'unsafe-eval' SOLO se agrega a script-src en las paginas admin que cargan
 * Alpine.js (dashboard, pagos, pedidos, productos - todo lo que usa
 * x-layouts.admin-dashboard), nunca en el sitio publico/checkout. Alpine
 * evalua sus expresiones (x-model, x-show, @click...) con new Function(), lo
 * que requiere unsafe-eval; sin el, TODO Alpine se rompe en silencio (cada
 * expresion tira "Alpine Expression Error" en consola) - los x-show quedan
 * sin poder ocultar nada (los modales aparecen ya abiertos) y los x-model no
 * sincronizan el input con el estado. admin.login no entra aqui: es un
 * formulario sin Alpine, no lo necesita.
 */
class SecurityHeaders
{
    private const RUTAS_ADMIN_CON_ALPINE_EXCLUIDAS = ['admin.login', 'admin.login.store', 'admin.logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(16);
        $request->attributes->set('csp_nonce', $nonce);
        View::share('cspNonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        $usaAlpineAdmin = $request->routeIs('admin.*')
            && ! $request->routeIs(...self::RUTAS_ADMIN_CON_ALPINE_EXCLUIDAS);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('Content-Security-Policy', $this->csp($nonce, $usaAlpineAdmin));

        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function csp(string $nonce, bool $usaAlpineAdmin): string
    {
        $scriptSrc = "script-src 'self' 'nonce-{$nonce}' cdn.jsdelivr.net *.micuentaweb.pe *.online-metrix.net";
        if ($usaAlpineAdmin) {
            $scriptSrc .= " 'unsafe-eval'";
        }

        $directivas = [
            "default-src 'self'",
            // El cliente Krypton (PopIn) reparte sus recursos entre varios
            // subdominios de micuentaweb.pe (static, secure, assets...), no
            // solo static.micuentaweb.pe: se usa comodin para no romper el
            // widget cada vez que Izipay agrega/cambia un subdominio interno.
            // *.online-metrix.net es ThreatMetrix: el fingerprint de
            // dispositivo que usa el analizador de riesgo/antifraude de
            // Izipay en cada intento de pago. Si se bloquea, Izipay no recibe
            // huella del dispositivo y tiende a rechazar la transaccion
            // igual, sea cual sea la tarjeta.
            $scriptSrc,
            "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com *.micuentaweb.pe",
            "font-src 'self' fonts.gstatic.com *.micuentaweb.pe",
            "img-src 'self' data: images.unsplash.com cdn.simpleicons.org *.micuentaweb.pe *.online-metrix.net",
            "connect-src 'self' *.micuentaweb.pe *.online-metrix.net",
            "frame-src 'self' *.micuentaweb.pe *.online-metrix.net www.google.com",
            "frame-ancestors 'self'",
            "form-action 'self' *.micuentaweb.pe",
            "object-src 'none'",
            "base-uri 'self'",
        ];

        return implode('; ', $directivas);
    }
}
