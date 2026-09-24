<?php

/*
|--------------------------------------------------------------------------
| Credenciales de Izipay
|--------------------------------------------------------------------------
|
| Se leen desde el archivo .env (NUNCA se escriben aquí en duro).
| - username / password : Basic Auth para crear el formToken (backend).
| - public_key          : se usa en el navegador para inicializar el
|                         cliente Krypton (checkout embebido/pop-in).
| - sha256_key           : llave HMAC-SHA-256 usada SOLO para validar la
|                         firma del retorno del navegador (kr-hash-key
|                         === 'sha256_hmac'). El IPN usa `password`.
|
| Usa las credenciales de TEST mientras desarrollas; cambia a producción
| solo en el servidor final.
|
*/

return [

    'username' => env('IZIPAY_USERNAME'),

    'password' => env('IZIPAY_PASSWORD'),

    'public_key' => env('IZIPAY_PUBLIC_KEY'),

    'sha256_key' => env('IZIPAY_SHA256_KEY'),

    'base_url' => env('IZIPAY_BASE_URL', 'https://api.micuentaweb.pe'),

    'js_client_url' => env(
        'IZIPAY_JS_CLIENT_URL',
        'https://static.micuentaweb.pe/static/js/krypton-client/V4.0/stable/kr-payment-form.min.js'
    ),

    'currency' => env('IZIPAY_CURRENCY', 'PEN'),

    // true precarga el modal de compra con datos de prueba (Juan Perez...).
    // Solo para certificar el checkout; apagalo (false) antes de recibir
    // clientes reales, para que no vean el formulario prellenado.
    'autollenar_test' => env('IZIPAY_AUTOLLENAR_TEST', false),

];
