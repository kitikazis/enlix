<?php

/*
|--------------------------------------------------------------------------
| Catálogo de productos de Enlix
|--------------------------------------------------------------------------
|
| Los precios SIEMPRE se definen aquí (en el servidor), en céntimos, que es
| la unidad que exige Culqi:  S/ 99.00  ->  9900.
|
| El backend toma el precio de este archivo, nunca del navegador, para que
| nadie pueda alterar el monto del pago desde el cliente.
|
| Más adelante este catálogo puede migrarse a base de datos sin cambiar el
| resto de la arquitectura (ver App\Support\Producto).
|
*/

return [

    'items' => [

        'plan-web-basico' => [
            'nombre'          => 'Plan Web Básico',
            'descripcion'     => 'Sitio web institucional listo para tu empresa, con diseño responsive y formulario de contacto.',
            'precio_centimos' => 9900,   // S/ 99.00
            'features'        => [
                'Hasta 5 secciones',
                'Diseño responsive',
                'Formulario de contacto',
                'Soporte por 1 mes',
            ],
        ],

        'plan-web-profesional' => [
            'nombre'          => 'Plan Web Profesional',
            'descripcion'     => 'Todo lo del plan básico más posicionamiento, blog y panel para gestionar tu contenido.',
            'precio_centimos' => 19900,  // S/ 199.00
            'features'        => [
                'Hasta 12 secciones',
                'Optimización SEO básica',
                'Blog y noticias',
                'Panel de administración',
                'Soporte por 3 meses',
            ],
        ],

        'plan-web-empresarial' => [
            'nombre'          => 'Plan Web Empresarial',
            'descripcion'     => 'Solución a medida con integraciones, tienda en línea y soporte prioritario para tu negocio.',
            'precio_centimos' => 34900,  // S/ 349.00
            'features'        => [
                'Secciones ilimitadas',
                'Tienda en línea',
                'Integraciones a medida',
                'Soporte prioritario por 6 meses',
            ],
        ],

    ],

];
