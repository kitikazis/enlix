<?php

use App\Http\Controllers\Admin\CategoriasController as AdminCategoriasController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\ModoController as AdminModoController;
use App\Http\Controllers\Admin\PaginasLegalesController as AdminPaginasLegalesController;
use App\Http\Controllers\Admin\PedidosController as AdminPedidosController;
use App\Http\Controllers\Admin\ProductosController as AdminProductosController;
use App\Http\Controllers\Admin\ReclamosController as AdminReclamosController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\IzipayController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ReclamoController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'inicio'])->name('inicio');
Route::get('/nosotros', [PageController::class, 'nosotros'])->name('nosotros');
Route::get('/contacto', [PageController::class, 'contacto'])->name('contacto');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// El nombre de ruta 'terminos' no cambia (checkout.blade.php y los tests ya
// lo usan): solo se mueve el path a /terminos-y-condiciones. El /terminos
// viejo queda como 301 por si hay enlaces o favoritos guardados.
Route::get('/terminos-y-condiciones', [PageController::class, 'terminos'])->name('terminos');
Route::redirect('/terminos', '/terminos-y-condiciones', 301);

Route::get('/politica-de-privacidad', [PageController::class, 'politicaDePrivacidad'])->name('politica-privacidad');

// Libro de Reclamaciones (obligatorio por ley). throttle:reclamos limita
// el POST por IP para que no se pueda usar como spam/DoS - ver
// AppServiceProvider.
Route::prefix('libro-de-reclamaciones')->name('libro-reclamaciones.')->group(function () {
    Route::get('/', [ReclamoController::class, 'index'])->name('index');
    Route::post('/', [ReclamoController::class, 'store'])
        ->middleware('throttle:reclamos')
        ->name('store');
});

// Tienda
Route::get('/productos', [IzipayController::class, 'index'])->name('productos');
Route::get('/productos/{producto:slug}', [ProductoController::class, 'show'])->name('productos.show');

// Carrito (invitado, por cookie - ver CarritoService). El pago (Izipay) va
// en /checkout, no aqui.
Route::prefix('carrito')->name('carrito.')->group(function () {
    Route::get('/', [CarritoController::class, 'index'])->name('index');
    Route::get('/resumen', [CarritoController::class, 'mostrar'])->name('resumen');
    Route::post('/items', [CarritoController::class, 'agregar'])
        ->middleware('throttle:60,1')
        ->name('items.store');
    Route::patch('/items/{item}', [CarritoController::class, 'actualizar'])
        ->middleware('throttle:60,1')
        ->name('items.update');
    Route::delete('/items/{item}', [CarritoController::class, 'eliminar'])
        ->middleware('throttle:60,1')
        ->name('items.destroy');
});

// Checkout del carrito (tabla `pedidos`, coexiste con /izipay/* de abajo -
// ver docblock de CheckoutController).
Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');

    Route::post('/', [CheckoutController::class, 'crear'])
        ->middleware('throttle:izipay')
        ->name('crear');

    Route::post('/validar', [CheckoutController::class, 'validar'])
        ->middleware('throttle:60,1')
        ->name('validar');

    // IPN de Izipay (notificacion servidor-servidor, exenta de CSRF en bootstrap/app.php)
    Route::post('/ipn', [CheckoutController::class, 'ipn'])
        ->middleware('throttle:60,1')
        ->name('ipn');
});

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
    // /admin y /admin/ (Laravel ignora la barra final) van al dashboard; si
    // no hay sesión, el middleware auth de /dashboard manda al login. Antes
    // /admin daba 404.
    Route::redirect('/', '/admin/dashboard')->name('inicio');

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
        Route::get('/dashboard/exportar', [AdminDashboardController::class, 'exportarCsv'])->name('dashboard.exportar');
        Route::get('/pagos', [AdminDashboardController::class, 'pagos'])->name('pagos');
        // Modo del panel (real/prueba, ver ModoController): preferencia de
        // sesion disponible desde cualquier pantalla del admin.
        Route::post('/modo', [AdminModoController::class, 'actualizar'])->name('modo.actualizar');
    });

    Route::middleware('auth')->prefix('pedidos')->name('pedidos.')->group(function () {
        Route::get('/', [AdminPedidosController::class, 'index'])->name('index');
        Route::get('/{pedido}', [AdminPedidosController::class, 'show'])->name('show');
    });

    Route::middleware('auth')->prefix('productos')->name('productos.')->group(function () {
        Route::get('/', [AdminProductosController::class, 'index'])->name('index');
        Route::get('/verificar-referencia', [AdminProductosController::class, 'verificarReferencia'])->name('verificar-referencia');
        // /papelera antes de /{producto}: si no, la ruta comodin la atraparia
        // como si "papelera" fuera un {producto} (id/slug) y nunca llegaria aqui.
        Route::get('/papelera', [AdminProductosController::class, 'papelera'])->name('papelera');
        Route::post('/', [AdminProductosController::class, 'store'])->name('store');
        Route::post('/{producto}/imagenes', [AdminProductosController::class, 'agregarImagen'])->name('imagenes.store');
        Route::delete('/{producto}/imagenes/{imagen}', [AdminProductosController::class, 'eliminarImagenGaleria'])->name('imagenes.destroy');
        Route::get('/{producto}', [AdminProductosController::class, 'show'])->name('show');
        Route::put('/{producto}', [AdminProductosController::class, 'update'])->name('update');
        Route::patch('/{producto}/alternar-activo', [AdminProductosController::class, 'alternarActivo'])->name('alternar-activo');
        Route::delete('/{producto}', [AdminProductosController::class, 'destroy'])->name('destroy');
        // withTrashed(): el binding implicito de Laravel excluye soft-deleted
        // por defecto: sin esto, restaurar un producto eliminado dar 404
        // (el {producto} de la ruta nunca lo encontraria).
        Route::patch('/{producto}/restaurar', [AdminProductosController::class, 'restaurar'])->name('restaurar')->withTrashed();
    });

    Route::middleware('auth')->prefix('categorias')->name('categorias.')->group(function () {
        Route::post('/', [AdminCategoriasController::class, 'store'])->name('store');
    });

    Route::middleware('auth')->prefix('reclamos')->name('reclamos.')->group(function () {
        Route::get('/', [AdminReclamosController::class, 'index'])->name('index');
        Route::get('/{reclamo}', [AdminReclamosController::class, 'show'])->name('show');
        Route::patch('/{reclamo}/atender', [AdminReclamosController::class, 'atender'])->name('atender');
    });

    Route::middleware('auth')->prefix('paginas-legales')->name('paginas-legales.')->group(function () {
        Route::get('/', [AdminPaginasLegalesController::class, 'index'])->name('index');
        Route::get('/{pagina}/editar', [AdminPaginasLegalesController::class, 'edit'])->name('edit');
        Route::put('/{pagina}', [AdminPaginasLegalesController::class, 'update'])->name('update');
    });
});

// Páginas de servicio: /servicio-mantenimiento-preventivo, /servicio-venta-equipos, etc.
Route::get('/servicio-{slug}', [ServicioController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('servicio.show');
