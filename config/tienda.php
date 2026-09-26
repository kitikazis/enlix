<?php

declare(strict_types=1);

return [

    // Recibe el aviso por correo de cada pedido que pasa a pagado. Si queda
    // vacío, simplemente no se manda ese correo (el del cliente sí se manda
    // siempre).
    'admin_email' => env('ADMIN_EMAIL'),

];
