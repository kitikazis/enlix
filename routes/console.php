<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recupera los pagos cuya IPN no llegó, preguntándole a Izipay.
Schedule::command('izipay:conciliar-pendientes')
    ->everyMinute()
    ->withoutOverlapping();

// Mismo rescate que arriba, pero para pedidos del carrito en 'en_verificacion'
// (autorizados en el navegador, IPN de captura nunca llegó).
Schedule::command('izipay:conciliar-pedidos-pendientes')
    ->everyMinute()
    ->withoutOverlapping();

// Cierra los que ya pasaron las 24h y Izipay confirma que nunca existieron.
Schedule::command('izipay:expirar-pendientes')
    ->hourly()
    ->withoutOverlapping();

// Libera el stock reservado de pedidos del carrito abandonados a mitad de
// checkout (más de 20 min en 'pendiente' sin pago real, confirmado con Izipay).
Schedule::command('pedidos:liberar-reservas-expiradas')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Red de seguridad más amplia (7 días) por si algo se escapó de las ventanas
// acotadas de los jobs de arriba (72h como mucho). También sirve a mano:
// php artisan izipay:reconcile --order=ENX-...
Schedule::command('izipay:reconcile')
    ->hourly()
    ->withoutOverlapping();
