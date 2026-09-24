<?php

namespace App\Providers;

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

        // Form-token de Izipay: limitado por IP + email, no solo por IP.
        RateLimiter::for('izipay', function (Request $request) {
            $email = Str::lower((string) $request->input('email', ''));

            return Limit::perMinute(5)->by($request->ip().'|'.$email);
        });

        // Login del panel admin: limitado por IP + email para frenar fuerza bruta.
        RateLimiter::for('admin-login', function (Request $request) {
            $email = Str::lower((string) $request->input('email', ''));

            return Limit::perMinute(5)->by($request->ip().'|'.$email);
        });

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
