<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Llaves de Culqi
    |--------------------------------------------------------------------------
    |
    | Se leen desde el archivo .env (NUNCA se escriben aquí en duro).
    | - public_key (pk_...) : se usa en el navegador para tokenizar la tarjeta.
    | - secret_key (sk_...) : se usa SOLO en el backend para crear el cargo.
    |
    | Las llaves pk_test_ / sk_test_ son de SANDBOX (no cobran dinero real).
    | Las llaves pk_live_ / sk_live_ son de PRODUCCIÓN (cobran de verdad).
    |
    */

    'public_key' => env('CULQI_PUBLIC_KEY'),

    'secret_key' => env('CULQI_SECRET_KEY'),

    'base_url' => env('CULQI_BASE_URL', 'https://api.culqi.com/v2'),

    'currency' => env('CULQI_CURRENCY', 'PEN'),

];
