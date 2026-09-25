<?php

namespace App\Providers;

use App\Services\CarritoService;
use App\Support\Catalogo;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Comparte los grupos de servicios con TODAS las vistas (menú, footer, sidebar).
        View::composer('*', function ($view) {
            $view->with('grupos', Catalogo::grupos());
        });

        // Contador del carrito para el ícono del header, visible en todas las
        // vistas. No crea un carrito nuevo si el visitante no tiene cookie
        // todavía (CarritoService::actual() devuelve null en ese caso) - solo
        // lee lo que ya exista, para no escribir en cada request.
        View::composer('*', function ($view) {
            $view->with('carritoCantidad', app(CarritoService::class)->cantidadTotal());
        });

        // Form-token de Izipay: limitado por IP + email, no solo por IP.
        RateLimiter::for('izipay', function (Request $request) {
            $email = Str::lower((string) $request->input('email', ''));

            return Limit::perMinute(5)->by($request->ip().'|'.$email);
        });

        // Login del panel admin: dos límites en paralelo. El de IP+email frena
        // el goteo desde una sola máquina; el de solo email frena el ataque
        // distribuido (varias IPs contra la misma cuenta), que el primero no ve.
        RateLimiter::for('admin-login', function (Request $request) {
            $email = Str::lower((string) $request->input('email', ''));

            return [
                Limit::perMinute(5)->by($request->ip().'|'.$email),
                Limit::perMinute(10)->by('email|'.$email),
            ];
        });

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
