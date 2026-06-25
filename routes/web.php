<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ServicioController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'inicio'])->name('inicio');
Route::get('/nosotros', [PageController::class, 'nosotros'])->name('nosotros');
Route::get('/contacto', [PageController::class, 'contacto'])->name('contacto');

// Tienda + pago con Culqi
Route::get('/productos', [CheckoutController::class, 'index'])->name('productos');
Route::post('/checkout/pagar', [CheckoutController::class, 'pagar'])->name('checkout.pagar');
Route::post('/checkout/orden', [CheckoutController::class, 'crearOrden'])->name('checkout.orden');
Route::post('/checkout/verificar-orden', [CheckoutController::class, 'verificarOrden'])->name('checkout.verificar');

// Webhook de Culqi (confirma pagos diferidos: agentes y bodegas / PagoEfectivo)
Route::post('/culqi/webhook', [CheckoutController::class, 'webhook'])->name('culqi.webhook');

// Páginas de servicio: /servicio-cctv, /servicio-distribucion-equipos, etc.
Route::get('/servicio-{slug}', [ServicioController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('servicio.show');
