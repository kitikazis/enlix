<?php

/*
|--------------------------------------------------------------------------
| Catálogo central de servicios - ENLIX
|--------------------------------------------------------------------------
| Cada servicio se trata como independiente. Las 9 entradas están
| agrupadas en 3 categorías para el menú, el sidebar y el footer.
*/

return [

    // Orden y metadatos de los grupos (para menú, sidebar y footer)
    'orden' => ['mantenimiento', 'gestion', 'venta'],

    'labels' => [
        'mantenimiento' => 'Mantenimiento y reparación',
        'gestion'       => 'Configuración y gestión TI',
        'venta'         => 'Venta de equipos y suministros',
    ],

    'nums' => [
        'mantenimiento' => '01',
        'gestion'       => '02',
        'venta'         => '03',
    ],

    // Catálogo de servicios (slug => datos).
    // 'equipos' es opcional: se muestra como etiquetas en la página del servicio.
    'items' => [

        // ===== 01 MANTENIMIENTO Y REPARACIÓN =====
        'diagnostico-tecnico' => [
            'group_slug'  => 'mantenimiento',
            'group_label' => 'Mantenimiento y reparación',
            'group_num'   => '01',
            'badge'       => 'Servicio 01.1',
            'title'       => 'Diagnóstico técnico especializado',
            'short'       => 'Diagnóstico técnico',
            'lead'        => 'Evaluación completa de laptops, PC, All-in-One, Mac e impresoras para identificar fallas de hardware o software.',
            'image'       => 'https://images.unsplash.com/photo-1555617981-dac3880eac6e?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Pruebas de rendimiento',
                'Revisión de componentes',
                'Análisis de logs del sistema',
                'Reporte técnico detallado con recomendaciones',
            ],
            'equipos'     => ['Laptops', 'PC', 'All-in-One', 'Mac', 'Impresoras'],
            'cta_title'   => '¿Tu equipo presenta fallas?',
            'cta_text'    => 'Agenda un diagnóstico y recibe un reporte claro con la solución recomendada.',
            'cta_btn'     => 'Agendar diagnóstico',
        ],

        'mantenimiento-preventivo' => [
            'group_slug'  => 'mantenimiento',
            'group_label' => 'Mantenimiento y reparación',
            'group_num'   => '01',
            'badge'       => 'Servicio 01.2',
            'title'       => 'Mantenimiento preventivo',
            'short'       => 'Mantenimiento preventivo',
            'lead'        => 'Servicio planificado para evitar fallas y prolongar la vida útil de los equipos.',
            'image'       => 'https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Limpieza interna profunda de polvo',
                'Cambio de pasta térmica',
                'Revisión y optimización de hardware',
                'Actualización de drivers, firmware y sistema operativo',
                'Optimización de rendimiento y limpieza de software',
                'Prueba de estrés y reporte técnico por equipo',
            ],
            'equipos'     => ['Laptops', 'PC', 'All-in-One (Windows)', 'MacBook', 'iMac', 'Mac Mini', 'Impresoras de todas las marcas'],
            'cta_title'   => '¿Quieres prevenir fallas antes de que ocurran?',
            'cta_text'    => 'Coordina un plan de mantenimiento preventivo para tu empresa.',
            'cta_btn'     => 'Coordinar visita',
        ],

        'mantenimiento-correctivo' => [
            'group_slug'  => 'mantenimiento',
            'group_label' => 'Mantenimiento y reparación',
            'group_num'   => '01',
            'badge'       => 'Servicio 01.3',
            'title'       => 'Mantenimiento correctivo y reparación',
            'short'       => 'Mantenimiento correctivo y reparación',
            'lead'        => 'Atención cuando el equipo ya presenta fallas: lo diagnosticamos, lo reparamos y lo devolvemos a la operación.',
            'image'       => 'https://images.unsplash.com/photo-1531492746076-161ca9bcad58?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Diagnóstico y reparación de hardware',
                'Cambio de componentes: disco SSD/HDD, memoria RAM, fuente de poder, pantalla, teclado, batería, etc.',
                'Reparación de placas',
                'Recuperación de sistema operativo',
            ],
            'equipos'     => ['Laptops', 'PC', 'All-in-One (Windows)', 'MacBook', 'iMac', 'Mac Mini', 'Impresoras de todas las marcas'],
            'cta_title'   => '¿Necesitas reparar un equipo?',
            'cta_text'    => 'Cuéntanos la falla y coordinamos la atención on-site o en nuestro local.',
            'cta_btn'     => 'Solicitar reparación',
        ],

        'mantenimiento-impresoras' => [
            'group_slug'  => 'mantenimiento',
            'group_label' => 'Mantenimiento y reparación',
            'group_num'   => '01',
            'badge'       => 'Servicio 01.4',
            'title'       => 'Limpieza y mantenimiento especializado de impresoras',
            'short'       => 'Mantenimiento de impresoras',
            'lead'        => 'Servicio específico para impresoras láser, inkjet y multifuncionales de todas las marcas.',
            'image'       => 'https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Limpieza de cabezales',
                'Limpieza de rodillos y fusores',
                'Calibración',
                'Actualización de firmware',
                'Prueba de impresión de calidad',
            ],
            'equipos'     => ['Láser', 'Inkjet', 'Multifuncionales', 'Todas las marcas'],
            'cta_title'   => '¿Tus impresiones salen con fallas?',
            'cta_text'    => 'Coordina el mantenimiento de tus impresoras y multifuncionales.',
            'cta_btn'     => 'Coordinar visita',
        ],

        // ===== 02 CONFIGURACIÓN Y GESTIÓN TI =====
        'instalacion-sistemas' => [
            'group_slug'  => 'gestion',
            'group_label' => 'Configuración y gestión TI',
            'group_num'   => '02',
            'badge'       => 'Servicio 02.1',
            'title'       => 'Instalación y configuración de sistemas operativos y software',
            'short'       => 'Instalación de sistemas y software',
            'lead'        => 'Instalación limpia de Windows o macOS, configuración de programas, drivers, perfiles de usuario y optimización inicial de estaciones de trabajo.',
            'image'       => 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Instalación limpia de Windows o macOS',
                'Configuración de programas y drivers',
                'Configuración de perfiles de usuario',
                'Optimización inicial de estaciones de trabajo',
            ],
            'cta_title'   => '¿Estrenas equipos o necesitas reinstalarlos?',
            'cta_text'    => 'Los dejamos instalados, configurados y listos para trabajar.',
            'cta_btn'     => 'Solicitar cotización',
        ],

        'migracion-estaciones' => [
            'group_slug'  => 'gestion',
            'group_label' => 'Configuración y gestión TI',
            'group_num'   => '02',
            'badge'       => 'Servicio 02.2',
            'title'       => 'Migración y configuración de estaciones de trabajo',
            'short'       => 'Migración de estaciones de trabajo',
            'lead'        => 'Traslado completo de datos, programas, configuraciones y perfiles de usuario de un equipo a otro. Ideal en renovaciones o cambios de personal.',
            'image'       => 'https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Traslado completo de datos',
                'Migración de programas y configuraciones',
                'Traspaso de perfiles de usuario',
                'Ideal en renovaciones de equipos o cambios de personal',
            ],
            'cta_title'   => '¿Vas a cambiar de equipos?',
            'cta_text'    => 'Migramos todo sin que pierdas información ni tiempo de trabajo.',
            'cta_btn'     => 'Planificar migración',
        ],

        'inventario-activos-ti' => [
            'group_slug'  => 'gestion',
            'group_label' => 'Configuración y gestión TI',
            'group_num'   => '02',
            'badge'       => 'Servicio 02.3',
            'title'       => 'Inventario y control de activos TI',
            'short'       => 'Inventario de activos TI',
            'lead'        => 'Levantamiento físico de equipos, etiquetado, registro de números de serie, estado actual y generación de una base de datos actualizable.',
            'image'       => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Levantamiento físico de equipos',
                'Etiquetado de activos',
                'Registro de números de serie y estado actual',
                'Base de datos actualizable',
            ],
            'cta_title'   => '¿Sabes cuántos equipos tiene tu empresa?',
            'cta_text'    => 'Ordenamos tu inventario TI para que tengas el control de tus activos.',
            'cta_btn'     => 'Solicitar cotización',
        ],

        'asesoria-renovacion' => [
            'group_slug'  => 'gestion',
            'group_label' => 'Configuración y gestión TI',
            'group_num'   => '02',
            'badge'       => 'Servicio 02.4',
            'title'       => 'Asesoría de renovación de equipos',
            'short'       => 'Asesoría de renovación',
            'lead'        => 'Evaluación del parque informático actual y recomendaciones técnicas y económicas para renovar equipos de forma ordenada y eficiente.',
            'image'       => 'https://images.unsplash.com/photo-1600880292203-757bb62b4baf?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Evaluación del parque informático actual',
                'Recomendaciones técnicas',
                'Recomendaciones económicas',
                'Plan de renovación ordenado y eficiente',
            ],
            'cta_title'   => '¿Es momento de renovar tus equipos?',
            'cta_text'    => 'Te ayudamos a decidir qué renovar, cuándo y con qué presupuesto.',
            'cta_btn'     => 'Solicitar asesoría',
        ],

        // ===== 03 VENTA DE EQUIPOS Y SUMINISTROS =====
        'venta-equipos' => [
            'group_slug'  => 'venta',
            'group_label' => 'Venta de equipos y suministros',
            'group_num'   => '03',
            'badge'       => 'Servicio 03.1',
            'title'       => 'Venta de equipos informáticos, accesorios y suministros de impresión',
            'short'       => 'Venta de equipos y suministros',
            'lead'        => 'Distribución de laptops, PC, All-in-One, Mac, impresoras, accesorios y consumibles (tóners, cartuchos, etc.) con asesoría personalizada.',
            'image'       => 'https://images.unsplash.com/photo-1588508065123-287b28e013da?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Laptops, PC, All-in-One y Mac',
                'Impresoras y multifuncionales',
                'Accesorios informáticos',
                'Consumibles: tóners, cartuchos y más',
                'Asesoría personalizada en cada compra',
            ],
            'cta_title'   => '¿Necesitas equipar a tu empresa?',
            'cta_text'    => 'Solicita una cotización personalizada de equipos y suministros.',
            'cta_btn'     => 'Solicitar cotización',
        ],

    ],

    // Servicios que ya no se ofrecen (slug viejo => slug nuevo, o null para
    // mandarlo a la sección de servicios del inicio). Se redirigen con 301
    // para no romper enlaces ni perder el posicionamiento ganado.
    'redirecciones' => [
        'distribucion-equipos'        => 'venta-equipos',
        'suministros-impresion'       => 'venta-equipos',
        'arrendamiento-equipos'       => 'venta-equipos',
        'infraestructura-tecnologica' => 'asesoria-renovacion',
        'consultoria-tecnologica'     => 'asesoria-renovacion',
        'outsourcing-ti'              => null,
        'cableado-estructurado'       => null,
        'desarrollo-web'              => null,
        'desarrollo-software'         => null,
        'cctv'                        => null,
        'microsoft-365'               => null,
        'hosting'                     => null,
        'erp'                         => null,
        'crm'                         => null,
    ],
];
