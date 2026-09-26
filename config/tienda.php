<?php

declare(strict_types=1);

return [

    // Recibe el aviso por correo de cada pedido que pasa a pagado. Si queda
    // vacío, simplemente no se manda ese correo (el del cliente sí se manda
    // siempre).
    'admin_email' => env('ADMIN_EMAIL'),

    // Costo de envío para pedidos con metodo_entrega = 'envio' (el recojo en
    // tienda nunca cobra envío, sin importar este flag). 'gratis' por
    // defecto porque todavía no hay una tarifa real definida - no se inventa
    // un monto. 'fijo' cobra siempre envio.tarifa_fija_centimos, sin
    // importar distrito/peso (no hay tarifario por zona todavía).
    'envio' => [
        'modo' => env('ENVIO_MODO', 'gratis'),
        'tarifa_fija_centimos' => (int) env('ENVIO_TARIFA_FIJA_CENTIMOS', 0),
    ],

];
