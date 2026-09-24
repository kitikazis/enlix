<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recupera los pagos cuya IPN no llegó, preguntándole a Izipay.
Schedule::command('izipay:conciliar-pendientes')
    ->everyTenMinutes()
    ->withoutOverlapping();

// Cierra los que ya pasaron las 24h y Izipay confirma que nunca existieron.
Schedule::command('izipay:expirar-pendientes')
    ->hourly()
    ->withoutOverlapping();
