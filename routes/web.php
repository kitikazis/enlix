<?php

use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\PagosController as AdminPagosController;
use App\Http\Controllers\IzipayController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ServicioController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'inicio'])->name('inicio');
Route::get('/nosotros', [PageController::class, 'nosotros'])->name('nosotros');
Route::get('/contacto', [PageController::class, 'contacto'])->name('contacto');

// Tienda + pago con Izipay
Route::get('/productos', [IzipayController::class, 'index'])->name('productos');

Route::post('/izipay/form-token', [IzipayController::class, 'formToken'])
    ->middleware('throttle:izipay')
    ->name('izipay.form-token');

Route::post('/izipay/validar', [IzipayController::class, 'validar'])
    ->middleware('throttle:60,1')
    ->name('izipay.validar');

// IPN de Izipay (notificacion servidor-servidor, exenta de CSRF en bootstrap/app.php)
Route::post('/izipay/ipn', [IzipayController::class, 'ipn'])
    ->middleware('throttle:60,1')
    ->name('izipay.ipn');

// Panel admin de solo lectura (ver pagos sin entrar a phpMyAdmin).
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'store'])
        ->middleware('throttle:admin-login')
        ->name('login.store');
    Route::post('/logout', [AdminLoginController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/pagos', [AdminPagosController::class, 'index'])->name('pagos.index');
    });
});

// Páginas de servicio: /servicio-cctv, /servicio-distribucion-equipos, etc.
Route::get('/servicio-{slug}', [ServicioController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('servicio.show');
