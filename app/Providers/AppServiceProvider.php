<?php

namespace App\Providers;

use App\Support\Catalogo;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
    }
}
