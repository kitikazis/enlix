<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
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

    // Dashboard y Productos exigen login siempre. El bypass ADMIN_SIN_LOGIN
    // que existia aqui se retiro: el dashboard muestra emails y tarjetas
    // enmascaradas de clientes reales, igual de sensible que los precios.
    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    });

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
