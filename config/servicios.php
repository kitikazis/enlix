<?php

/*
|--------------------------------------------------------------------------
| Catálogo central de servicios - ENLIX
|--------------------------------------------------------------------------
| Cada servicio se trata como independiente. Las 15 entradas están
| agrupadas en 5 categorías para el menú, el sidebar y el footer.
| Migrado desde includes/servicios-data.php del sitio original.
*/

return [

    // Orden y metadatos de los grupos (para menú, sidebar y footer)
    'orden' => ['equipos', 'soporte', 'desarrollo', 'seguridad', 'consultoria'],

    'labels' => [
        'equipos'     => 'Equipos y suministros',
        'soporte'     => 'Soporte e infraestructura',
        'desarrollo'  => 'Desarrollo web y software',
        'seguridad'   => 'Seguridad y servicios cloud',
        'consultoria' => 'Consultoría, ERP y CRM',
    ],

    'nums' => [
        'equipos'     => '01',
        'soporte'     => '02',
        'desarrollo'  => '03',
        'seguridad'   => '04',
        'consultoria' => '05',
    ],

    // Catálogo de servicios (slug => datos)
    'items' => [

        // ===== 01 EQUIPOS Y SUMINISTROS =====
        'distribucion-equipos' => [
            'group_slug'  => 'equipos',
            'group_label' => 'Equipos y suministros',
            'group_num'   => '01',
            'badge'       => 'Servicio 01.1',
            'title'       => 'Distribuidores de equipos y accesorios tecnológicos',
            'short'       => 'Distribución de equipos',
            'lead'        => 'Suministramos computadoras, laptops, impresoras, tablets, servidores y accesorios tecnológicos de marcas reconocidas para empresas. Brindamos asesoría para elegir la solución más adecuada, asegurando calidad, garantía y continuidad operativa.',
            'image'       => 'https://images.unsplash.com/photo-1593640408182-31c70c8268f5?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Computadoras de escritorio, laptops y workstations',
                'Servidores, almacenamiento y equipos de red',
                'Impresoras, multifuncionales y tablets',
                'Asesoría en marcas, garantía y postventa',
            ],
            'cta_title'   => '¿Necesitas equipar a tu empresa?',
            'cta_text'    => 'Solicita una cotización personalizada para tu equipo de trabajo.',
            'cta_btn'     => 'Solicitar cotización',
        ],

        'suministros-impresion' => [
            'group_slug'  => 'equipos',
            'group_label' => 'Equipos y suministros',
            'group_num'   => '01',
            'badge'       => 'Servicio 01.2',
            'title'       => 'Distribuidores de suministros de impresión',
            'short'       => 'Suministros de impresión',
            'lead'        => 'Proveemos cartuchos, tóners y suministros de impresión originales y compatibles para garantizar el correcto funcionamiento de los equipos de nuestros clientes. Aseguramos disponibilidad, calidad y asesoría para optimizar los costos de impresión en las empresas.',
            'image'       => 'https://images.unsplash.com/photo-1563770660941-20978e870e26?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Tóners y cartuchos originales y compatibles',
                'Disponibilidad continua y reposición programada',
                'Optimización del costo por página',
                'Atención de pedidos urgentes',
            ],
            'cta_title'   => '¿Quieres optimizar tus costos de impresión?',
            'cta_text'    => 'Asesoramos a tu empresa para reducir gastos y asegurar stock.',
            'cta_btn'     => 'Solicitar asesoría',
        ],

        'mantenimiento-preventivo' => [
            'group_slug'  => 'equipos',
            'group_label' => 'Equipos y suministros',
            'group_num'   => '01',
            'badge'       => 'Servicio 01.3',
            'title'       => 'Mantenimiento preventivo de equipos',
            'short'       => 'Mantenimiento preventivo',
            'lead'        => 'Realizamos revisiones periódicas, limpieza y optimización de computadoras y equipos tecnológicos para prevenir fallas y mejorar su rendimiento. Este servicio ayuda a prolongar la vida útil de los equipos y asegurar la continuidad de las operaciones en la empresa.',
            'image'       => 'https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Revisiones periódicas planificadas',
                'Limpieza interna y externa de equipos',
                'Optimización de software y rendimiento',
                'Reportes técnicos por equipo',
            ],
            'cta_title'   => '¿Quieres prevenir fallas antes de que ocurran?',
            'cta_text'    => 'Coordina un plan de mantenimiento preventivo para tu empresa.',
            'cta_btn'     => 'Coordinar visita',
        ],

        // ===== 02 SOPORTE E INFRAESTRUCTURA =====
        'arrendamiento-equipos' => [
            'group_slug'  => 'soporte',
            'group_label' => 'Soporte e infraestructura',
            'group_num'   => '02',
            'badge'       => 'Servicio 02.1',
            'title'       => 'Arrendamiento de equipos tecnológicos',
            'short'       => 'Arrendamiento de equipos',
            'lead'        => 'Ofrecemos el alquiler de computadoras, laptops, impresoras y otros equipos tecnológicos para que las empresas cuenten con la tecnología que necesitan sin realizar grandes inversiones iniciales. Brindamos equipos confiables, soporte técnico y renovación tecnológica para asegurar la continuidad de las operaciones.',
            'image'       => 'https://images.unsplash.com/photo-1531297484001-80022131f5a1?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Equipos confiables configurados según la necesidad',
                'Soporte técnico incluido durante el arrendamiento',
                'Renovación tecnológica planificada',
                'Sin inversión inicial elevada',
            ],
            'cta_title'   => '¿Necesitas equipos sin grandes inversiones?',
            'cta_text'    => 'Cotiza el arrendamiento de equipos para tu operación.',
            'cta_btn'     => 'Cotizar arrendamiento',
        ],

        'outsourcing-ti' => [
            'group_slug'  => 'soporte',
            'group_label' => 'Soporte e infraestructura',
            'group_num'   => '02',
            'badge'       => 'Servicio 02.2',
            'title'       => 'Outsourcing de personal TI',
            'short'       => 'Outsourcing de personal TI',
            'lead'        => 'Proveemos profesionales especializados en tecnología de la información para apoyar o gestionar el área de TI de las empresas. Nuestro personal brinda soporte, administración y acompañamiento técnico para garantizar la continuidad y eficiencia de los servicios tecnológicos.',
            'image'       => 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Soporte técnico in-situ o remoto',
                'Administración de plataformas y usuarios',
                'Acompañamiento de proyectos tecnológicos',
                'Personal calificado y disponible',
            ],
            'cta_title'   => '¿Necesitas reforzar tu área de TI?',
            'cta_text'    => 'Asignamos profesionales calificados para tu operación.',
            'cta_btn'     => 'Conversemos',
        ],

        'cableado-estructurado' => [
            'group_slug'  => 'soporte',
            'group_label' => 'Soporte e infraestructura',
            'group_num'   => '02',
            'badge'       => 'Servicio 02.3',
            'title'       => 'Cableado estructurado',
            'short'       => 'Cableado estructurado',
            'lead'        => 'Diseñamos e implementamos sistemas de cableado estructurado que permiten una conectividad estable y organizada para redes de datos, voz y otros servicios tecnológicos. Garantizamos instalaciones seguras, ordenadas y escalables que facilitan el crecimiento y la eficiencia de la infraestructura de red.',
            'image'       => 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Diseño según normas vigentes',
                'Instalación de redes de datos y voz',
                'Etiquetado, certificación y documentación',
                'Escalabilidad para crecimiento futuro',
            ],
            'cta_title'   => '¿Tu red necesita orden y certificación?',
            'cta_text'    => 'Coordina una visita técnica para evaluar tu cableado.',
            'cta_btn'     => 'Coordinar visita',
        ],

        // ===== 03 DESARROLLO WEB Y SOFTWARE =====
        'infraestructura-tecnologica' => [
            'group_slug'  => 'desarrollo',
            'group_label' => 'Desarrollo web y software',
            'group_num'   => '03',
            'badge'       => 'Servicio 03.1',
            'title'       => 'Infraestructura tecnológica',
            'short'       => 'Infraestructura tecnológica',
            'lead'        => 'Diseñamos e implementamos la infraestructura tecnológica necesaria para que las empresas operen de manera segura, eficiente y estable. Incluye servidores, redes, almacenamiento y soluciones que soportan el funcionamiento diario de sus sistemas y servicios.',
            'image'       => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Servidores y virtualización',
                'Redes corporativas y Wi-Fi empresarial',
                'Almacenamiento y respaldos',
                'Diseño integral y documentado',
            ],
            'cta_title'   => '¿Necesitas una infraestructura sólida?',
            'cta_text'    => 'Diseñamos la base tecnológica que tu operación requiere.',
            'cta_btn'     => 'Solicitar diagnóstico',
        ],

        'desarrollo-web' => [
            'group_slug'  => 'desarrollo',
            'group_label' => 'Desarrollo web y software',
            'group_num'   => '03',
            'badge'       => 'Servicio 03.2',
            'title'       => 'Desarrollo de páginas web',
            'short'       => 'Desarrollo de páginas web',
            'lead'        => 'Diseñamos y desarrollamos sitios web modernos, funcionales y adaptados a las necesidades de cada empresa. Creamos plataformas optimizadas que fortalecen la presencia digital y facilitan la conexión con clientes.',
            'image'       => 'https://images.unsplash.com/photo-1467232004584-a241de8bcf5d?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Sitios corporativos e informativos',
                'Diseño responsive y optimizado para SEO',
                'Integración con redes sociales y formularios',
                'Hosting y soporte posterior',
            ],
            'cta_title'   => '¿Quieres una página web profesional?',
            'cta_text'    => 'Diseñamos tu presencia digital con base en tu marca.',
            'cta_btn'     => 'Iniciar proyecto',
        ],

        'desarrollo-software' => [
            'group_slug'  => 'desarrollo',
            'group_label' => 'Desarrollo web y software',
            'group_num'   => '03',
            'badge'       => 'Servicio 03.3',
            'title'       => 'Desarrollo de software y aplicaciones',
            'short'       => 'Desarrollo de software',
            'lead'        => 'Diseñamos y desarrollamos soluciones de software a medida que optimizan procesos y mejoran la eficiencia de las empresas. Creamos aplicaciones funcionales, seguras y escalables adaptadas a las necesidades específicas de cada cliente.',
            'image'       => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Aplicaciones web y de escritorio',
                'Aplicaciones móviles para iOS y Android',
                'Automatización de procesos internos',
                'Integración con sistemas existentes',
            ],
            'cta_title'   => '¿Tienes una idea de software o aplicación?',
            'cta_text'    => 'Cuéntanos los detalles y te entregamos una propuesta clara.',
            'cta_btn'     => 'Iniciar proyecto',
        ],

        // ===== 04 SEGURIDAD Y SERVICIOS CLOUD =====
        'cctv' => [
            'group_slug'  => 'seguridad',
            'group_label' => 'Seguridad y servicios cloud',
            'group_num'   => '04',
            'badge'       => 'Servicio 04.1',
            'title'       => 'CCTV — Sistemas de videovigilancia',
            'short'       => 'CCTV (videovigilancia)',
            'lead'        => 'Implementamos soluciones de videovigilancia con cámaras de seguridad que permiten monitorear y proteger las instalaciones en tiempo real. Brindamos equipos, instalación y configuración para garantizar control, seguridad y respaldo de las operaciones.',
            'image'       => 'https://images.unsplash.com/photo-1557597774-9d273605dfa9?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Cámaras IP y analógicas de marcas reconocidas',
                'Instalación, configuración y puesta en marcha',
                'Monitoreo remoto desde dispositivos móviles',
                'Almacenamiento y respaldo de grabaciones',
            ],
            'cta_title'   => '¿Quieres proteger tus instalaciones?',
            'cta_text'    => 'Te asesoramos para implementar el sistema CCTV adecuado.',
            'cta_btn'     => 'Solicitar asesoría',
        ],

        'microsoft-365' => [
            'group_slug'  => 'seguridad',
            'group_label' => 'Seguridad y servicios cloud',
            'group_num'   => '04',
            'badge'       => 'Servicio 04.2',
            'title'       => 'Microsoft 365',
            'short'       => 'Microsoft 365',
            'lead'        => 'Proveemos licencias de Microsoft 365 para empresas, incluyendo herramientas como correo empresarial, almacenamiento en la nube y aplicaciones de productividad. Brindamos asesoría, configuración y soporte para optimizar el trabajo colaborativo y la gestión de la información.',
            'image'       => 'https://images.unsplash.com/photo-1633419461186-7d40a38105ec?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Correo empresarial con dominio propio',
                'OneDrive, SharePoint y Teams',
                'Office 365 (Word, Excel, PowerPoint, Outlook)',
                'Migración, configuración y soporte',
            ],
            'cta_title'   => '¿Quieres licencias Microsoft 365 para tu equipo?',
            'cta_text'    => 'Te asesoramos en la elección del plan adecuado.',
            'cta_btn'     => 'Cotizar licencias',
        ],

        'hosting' => [
            'group_slug'  => 'seguridad',
            'group_label' => 'Seguridad y servicios cloud',
            'group_num'   => '04',
            'badge'       => 'Servicio 04.3',
            'title'       => 'Hosting',
            'short'       => 'Hosting',
            'lead'        => 'Ofrecemos servicios de alojamiento web seguros y confiables para mantener páginas y aplicaciones disponibles en todo momento. Garantizamos rendimiento, respaldo de información y soporte técnico para asegurar la continuidad de los servicios digitales.',
            'image'       => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Alojamiento web y servidores virtuales',
                'Backups periódicos y certificados SSL',
                'Alta disponibilidad y rendimiento',
                'Soporte técnico permanente',
            ],
            'cta_title'   => '¿Buscas un hosting confiable?',
            'cta_text'    => 'Mantén tu sitio o aplicación siempre disponible.',
            'cta_btn'     => 'Cotizar hosting',
        ],

        // ===== 05 CONSULTORÍA, ERP Y CRM =====
        'erp' => [
            'group_slug'  => 'consultoria',
            'group_label' => 'Consultoría, ERP y CRM',
            'group_num'   => '05',
            'badge'       => 'Servicio 05.1',
            'title'       => 'ERP — Sistema de gestión empresarial',
            'short'       => 'Implementación de ERP',
            'lead'        => 'Implementamos soluciones ERP que integran y automatizan procesos clave como ventas, compras, inventarios y finanzas en una sola plataforma. Brindamos asesoría, implementación y soporte para mejorar el control, la eficiencia y la toma de decisiones en la empresa.',
            'image'       => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Ventas, compras e inventarios integrados',
                'Contabilidad y finanzas en una sola plataforma',
                'Asesoría e implementación por fases',
                'Capacitación a usuarios y soporte continuo',
            ],
            'cta_title'   => '¿Quieres integrar tus procesos en un ERP?',
            'cta_text'    => 'Agenda una sesión de diagnóstico con nuestros consultores.',
            'cta_btn'     => 'Agendar diagnóstico',
        ],

        'crm' => [
            'group_slug'  => 'consultoria',
            'group_label' => 'Consultoría, ERP y CRM',
            'group_num'   => '05',
            'badge'       => 'Servicio 05.2',
            'title'       => 'CRM — Gestión de relaciones con clientes',
            'short'       => 'Implementación de CRM',
            'lead'        => 'Implementamos soluciones CRM que permiten organizar, gestionar y dar seguimiento a clientes y oportunidades de negocio. Brindamos asesoría y configuración para mejorar las ventas, la atención y la fidelización de los clientes.',
            'image'       => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Gestión de oportunidades y pipeline de ventas',
                'Seguimiento de clientes y atención postventa',
                'Automatización de campañas y comunicaciones',
                'Reportes y métricas de gestión comercial',
            ],
            'cta_title'   => '¿Necesitas potenciar tu gestión comercial?',
            'cta_text'    => 'Te ayudamos a elegir e implementar el CRM adecuado.',
            'cta_btn'     => 'Conversemos',
        ],

        'consultoria-tecnologica' => [
            'group_slug'  => 'consultoria',
            'group_label' => 'Consultoría, ERP y CRM',
            'group_num'   => '05',
            'badge'       => 'Servicio 05.3',
            'title'       => 'Consultoría tecnológica',
            'short'       => 'Consultoría tecnológica',
            'lead'        => 'Brindamos asesoría especializada para evaluar, planificar y mejorar la infraestructura y procesos tecnológicos de las empresas. Proponemos soluciones estratégicas que optimizan costos, aumentan la eficiencia y alinean la tecnología con los objetivos del negocio.',
            'image'       => 'https://images.unsplash.com/photo-1600880292203-757bb62b4baf?auto=format&fit=crop&w=1400&q=80',
            'bullets'     => [
                'Diagnóstico tecnológico integral',
                'Planeamiento estratégico de TI',
                'Optimización de costos y recursos',
                'Alineación de tecnología con el negocio',
            ],
            'cta_title'   => '¿Quieres planificar mejor tu tecnología?',
            'cta_text'    => 'Te acompañamos a tomar decisiones estratégicas.',
            'cta_btn'     => 'Solicitar diagnóstico',
        ],

    ],
];
