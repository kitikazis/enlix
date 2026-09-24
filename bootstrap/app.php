<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        // Proxies de confianza. NUNCA '*' en producción: enlix.pe corre sobre
        // LiteSpeed sirviendo directo (sin Cloudflare ni balanceador), así que
        // confiar en X-Forwarded-For permitiría a cualquiera falsificar su IP
        // y evadir los rate limiters (verificado: 7/7 intentos de login sin
        // 429 rotando la cabecera). En local sí se usa '*' porque ngrok
        // termina TLS y sin eso Laravel genera URLs http:// (mixed content).
        //
        // TRUSTED_PROXIES acepta una lista separada por comas (o '*') para
        // cuando se ponga un CDN/balanceador delante. bootstrap/app.php es el
        // único punto donde env() es legítimo: la config aún no está cargada.
        $proxiesConfigurados = trim((string) env('TRUSTED_PROXIES', ''));

        $middleware->trustProxies(at: match (true) {
            $proxiesConfigurados === '*' => '*',
            $proxiesConfigurados !== '' => explode(',', $proxiesConfigurados),
            env('APP_ENV') === 'local' => '*',
            default => [],
        });

        // No hay ruta 'login' (solo 'admin.login'); sin esto, un invitado que
        // intenta entrar a /admin/pagos recibe un 401 en vez de un redirect.
        $middleware->redirectGuestsTo('/admin/login');

        // El IPN de Izipay es llamado por Izipay (sin sesión/CSRF); la firma
        // HMAC (kr-hash) es lo que garantiza la integridad de esta ruta.
        $middleware->validateCsrfTokens(except: [
            'izipay/ipn',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
