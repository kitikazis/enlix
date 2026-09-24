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

        // Sin esto, detrás de cualquier proxy que termina TLS (ngrok, Nginx,
        // balanceador de carga) Laravel ve la petición interna como HTTP y
        // genera route()/url() con http://, rompiendo fetch() por mixed
        // content en una página servida por https. Se confía en el header
        // X-Forwarded-Proto (y el resto de X-Forwarded-*) de cualquier proxy
        // inmediato; no es un riesgo nuevo porque la app nunca queda expuesta
        // directamente a internet sin un proxy delante en ningun despliegue real.
        $middleware->trustProxies(at: '*');

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
