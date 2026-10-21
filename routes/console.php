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

// --- Housekeeping de disco (hosting compartido, cuota de 1 GB) ---

// Único worker de colas: no hay proceso persistente en este hosting, así
// que cada minuto se levanta un worker que procesa lo pendiente (hoy solo
// los 2 mailables ShouldQueue de confirmación de pedido pagado) y termina
// solo en vez de quedarse escuchando (--stop-when-empty evita acumular
// procesos PHP colgados en el servidor compartido). Va como string y no
// como ['--stop-when-empty' => true]: el array lo convierte en
// --stop-when-empty='1' y queue:work lo rechaza (la opción no lleva valor),
// así que la cola nunca corría y el log crecía ~10 MB/día con el error.
Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('queue:prune-failed', ['--hours' => 168])->daily();
Schedule::command('queue:prune-batches', ['--hours' => 48])->daily();
Schedule::command('auth:clear-resets')->daily();
Schedule::command('model:prune')->daily();
Schedule::command('app:limpiar-temporales')->daily();
