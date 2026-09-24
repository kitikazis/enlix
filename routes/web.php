<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\PagosController as AdminPagosController;
use App\Http\Controllers\Admin\ProductosController as AdminProductosController;
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

    // ADMIN_SIN_LOGIN=true en .env quita el login temporalmente (solo pagos,
    // solo lectura). Ponlo en false (o bórralo) para volver a exigir login.
    $middlewarePagos = env('ADMIN_SIN_LOGIN', false) ? [] : ['auth'];

    Route::middleware($middlewarePagos)->group(function () {
        Route::get('/pagos', [AdminPagosController::class, 'index'])->name('pagos.index');
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    });

    // Productos SIEMPRE exige login, sin excepcion de ADMIN_SIN_LOGIN: aqui
    // se edita el precio real que se cobra en el checkout, es mas sensible
    // que solo consultar pagos.
    Route::middleware('auth')->prefix('productos')->name('productos.')->group(function () {
        Route::get('/', [AdminProductosController::class, 'index'])->name('index');
        Route::get('/crear', [AdminProductosController::class, 'create'])->name('create');
        Route::post('/', [AdminProductosController::class, 'store'])->name('store');
        Route::get('/{producto}/editar', [AdminProductosController::class, 'edit'])->name('edit');
        Route::put('/{producto}', [AdminProductosController::class, 'update'])->name('update');
        Route::patch('/{producto}/alternar-activo', [AdminProductosController::class, 'alternarActivo'])->name('alternar-activo');
    });
});

// Páginas de servicio: /servicio-cctv, /servicio-distribucion-equipos, etc.
Route::get('/servicio-{slug}', [ServicioController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('servicio.show');
