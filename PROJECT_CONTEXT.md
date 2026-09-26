# PROJECT_CONTEXT.md — Enlix (sitio web + e-commerce)

> Informe de arquitectura generado por análisis estático del repositorio (solo lectura). Todo lo aquí escrito está basado en el código real citado con `ruta/archivo.php:línea`. Cuando algo no pudo confirmarse en el código, se marca explícitamente como **SUPUESTO** o **NO ENCONTRADO**.
>
> Reemplaza la versión anterior de este archivo (fechada 2026-09-22), que describía una versión del proyecto anterior a la migración de pagos a Izipay y a la construcción del e-commerce (carrito/pedidos/stock/admin). Esta versión refleja el estado tras las Fases 1-4 del rediseño de e-commerce.
>
> Generado: 2026-09-25.

---

## 1. RESUMEN GENERAL

**Enlix** es el sitio web corporativo de una empresa peruana (Lima, Perú) de servicios de TI B2B. El sitio tiene tres propósitos:

1. **Vitrina institucional**: presenta la empresa, 15 servicios de TI agrupados en 5 categorías (equipos, soporte, desarrollo, seguridad, consultoría) y datos de contacto (`config/servicios.php`, vía `App\Support\Catalogo`).
2. **Tienda con carrito** (`/productos`, `/carrito`, `/checkout`): catálogo de productos (originalmente 3 "planes web", ahora también componentes de PC) gestionado desde base de datos y desde el panel admin, con carrito multi-producto persistente por cookie de invitado, control de stock con reservas, y checkout con pago vía **Izipay** (cliente Krypton/PopIn).
3. **Panel admin** (`/admin/*`): login con guard `web` estándar (cualquier fila de `users` es "admin", sin roles/permisos), gestión de productos, dashboard de métricas de pagos.

**Tipo de proyecto**: Monolito Laravel 12, renderizado 100% en servidor con Blade (no es SPA, no hay `routes/api.php`). El frontend público usa Bootstrap 5 (CDN) + JS vanilla; el panel admin está dividido entre un layout viejo en Bootstrap (login, CRUD de productos) y un dashboard nuevo en Tailwind v4 (vía Vite) + Alpine.js (CDN).

**Cambio de pasarela de pago**: el proyecto usó **Culqi** en una etapa anterior; hoy usa **Izipay** (plataforma Lyra/micuentaweb.pe) de forma exclusiva. No queda código de Culqi en el repositorio.

---

## 2. STACK TECNOLÓGICO

| Categoría | Detalle | Evidencia |
|---|---|---|
| Lenguaje | PHP `^8.2` | `composer.json` |
| Framework | Laravel Framework `^12.0` | `composer.json` |
| Testing | PHPUnit `^11.5` (NO Pest, aunque el scaffold de composer lo menciona) | `composer.json`, `phpunit.xml` |
| Base de datos (dev/test) | SQLite en memoria para tests (`phpunit.xml`); en producción **MySQL** | `phpunit.xml`, `.env` de producción |
| Sesión | Driver `database` | `config/session.php` |
| Caché | Driver `database`; usado además por `App\Support\Producto` para cachear el catálogo 60s | `app/Support/Producto.php` |
| Colas | Driver `database` (`QUEUE_CONNECTION=database`) | `.env.example` |
| Correo | Driver `log` en local/testing; SMTP de cPanel en producción vía `MAIL_HOST`/`MAIL_PORT=465`/`MAIL_ENCRYPTION=ssl` | `.env.example`, `config/mail.php` |
| Pasarela de pago | **Izipay** (Perú), plataforma Lyra/micuentaweb.pe, API REST V4 (`Charge/CreatePayment`, `Order/Get`) vía `Illuminate\Support\Facades\Http` | `app/Services/IzipayService.php` |
| Checkout cliente | Cliente **Krypton** (`kr-embedded`/`kr-popin`) cargado desde `static.micuentaweb.pe`, con `kr-language="es-Es"` | `resources/views/checkout.blade.php` |
| Frontend público (CSS/JS) | Bootstrap 5.3.2 vía CDN + `public/assets/css/styles.css` + `public/assets/js/enlix.js` (JS vanilla, sin framework) | `resources/views/layouts/app.blade.php` |
| Frontend admin (viejo) | Igual que el público: Bootstrap 5 CDN | `resources/views/layouts/admin.blade.php` |
| Frontend admin (nuevo, solo dashboard) | Tailwind v4 vía Vite + Alpine.js 3.14.1 (CDN, no es dependencia npm) | `resources/views/components/layouts/admin-dashboard.blade.php` |
| Build tool | Vite `^7` + `@tailwindcss/vite` — usado SOLO por `resources/css/admin.css` (dashboard nuevo); el sitio público y el resto del admin no pasan por Vite | `vite.config.js` |
| Storage | Disk `local` y `public` configurados; **symlink `public/storage` NO creado todavía** (bloquea imágenes de producto) | `config/filesystems.php` |

---

## 3. ESTRUCTURA DE CARPETAS (resumen)

```
enlix/
├── app/
│   ├── Console/Commands/
│   │   ├── AdminCrearUsuario.php
│   │   ├── IzipayConciliarPendientes.php       # concilia pagos (tabla pagos) contra Izipay
│   │   ├── IzipayExpirarPendientes.php         # expira pagos pendientes de 24h+
│   │   └── PedidosLiberarReservasExpiradas.php # libera stock de checkouts abandonados (pedidos)
│   ├── Enums/
│   │   ├── EstadoPago.php     # pendiente|en_verificacion|pagado|rechazado|anulado|expirado (compartido por pagos y pedidos)
│   │   └── EstadoEnvio.php    # pendiente|preparando|enviado|entregado|cancelado (solo pedidos, logística)
│   ├── Http/Controllers/
│   │   ├── PageController.php, ServicioController.php   # institucional
│   │   ├── IzipayController.php     # flujo VIEJO: /productos, /izipay/form-token|validar|ipn, tabla pagos
│   │   ├── CarritoController.php    # /carrito y /carrito/items (CRUD del carrito)
│   │   ├── CheckoutController.php   # flujo NUEVO: /checkout, tabla pedidos
│   │   └── Admin/
│   │       ├── LoginController.php, DashboardController.php, ProductosController.php
│   ├── Models/
│   │   ├── User.php, Pago.php                                   # preexistentes
│   │   └── Categoria.php, Marca.php, Producto.php (extendido),  # Fase 1 (catálogo)
│   │       ImagenProducto.php
│   │   └── Carrito.php, ItemCarrito.php                         # Fase 2 (carrito)
│   │   └── Pedido.php, ItemPedido.php, MovimientoStock.php      # Fase 3-4 (pedidos/stock)
│   ├── Services/
│   │   ├── IzipayService.php    # cliente HTTP genérico Izipay (formToken, consultarOrden, verificarFirma) - reusado por ambos flujos
│   │   ├── PagoService.php      # máquina de estados de `pagos` (flujo viejo)
│   │   ├── CarritoService.php   # alta/baja de items del carrito, cookie de invitado
│   │   ├── StockService.php     # reservar/confirmar/liberar stock, bitácora en movimientos_stock
│   │   ├── PedidoService.php    # convierte carrito en pedido, reserva stock
│   │   └── PedidoPagoService.php # máquina de estados de `pedidos` (flujo nuevo) - espejo de PagoService
│   └── Support/
│       ├── Catalogo.php    # servicios (config/servicios.php)
│       └── Producto.php    # fachada cacheada sobre el modelo Producto (BD), consumida por IzipayController
├── database/migrations/    # ver §4
├── resources/views/
│   ├── productos.blade.php   # catálogo público - botón "Agregar al carrito" (YA NO tiene el PopIn de Izipay)
│   ├── carrito.blade.php     # /carrito
│   ├── checkout.blade.php    # /checkout - aquí SÍ vive el PopIn de Izipay
│   └── admin/                # login, productos (index/form), dashboard
├── routes/web.php            # todas las rutas (no hay routes/api.php)
├── routes/console.php        # Schedule::command() de los 3 comandos de conciliación/expiración
└── tests/Feature/            # ver §6
```

---

## 4. BASE DE DATOS

### Tablas del flujo VIEJO (Izipay de 1 producto, tabla `pagos`)

| Tabla | Migración | Notas |
|---|---|---|
| `pagos` | `2026_06_25_000000_create_pagos_table.php` + `2026_09_22_000000_add_izipay_fields_to_pagos_table.php` + `2026_09_24_000000_add_unique_transaction_uuid_to_pagos_table.php` | `producto` es el **slug** (string, sin FK, a propósito). `estado` string (valores de `EstadoPago`). `izipay_order_id` único, `transaction_uuid` único, `card_brand`, `card_masked_pan`, `respuesta` (json). |

### Tablas del catálogo (Fase 1)

| Tabla | Migración | Notas |
|---|---|---|
| `productos` | `2026_09_24_010000_create_productos_table.php` + `2026_09_25_000003_add_ecommerce_fields_to_productos_table.php` | Empezó como catálogo de 3 "planes" (`slug`, `nombre`, `descripcion`, `precio_centimos`, `features` json, `orden`, `activo`). Se extendió con `categoria_id`, `marca_id`, `sku` (único, nullable), `descripcion_corta`, `especificaciones` (json), `precio_comparacion_centimos`, `stock`, `stock_reservado`, `umbral_stock_bajo`, `meses_garantia`, `peso_gramos`, `destacado`. **Sin soft-deletes**: `activo` cumple ese rol (nunca se borra un producto, ver `Admin\ProductosController::alternarActivo`). |
| `categorias` | `2026_09_25_000001_create_categorias_table.php` | `nombre`, `slug`, `categoria_padre_id` (auto-referencia, para subcategorías). |
| `marcas` | `2026_09_25_000002_create_marcas_table.php` | `nombre`, `slug`. |
| `imagenes_producto` | `2026_09_25_000004_create_imagenes_producto_table.php` | `producto_id` (FK, cascade), `ruta`, `texto_alternativo`, `orden`. |

### Tablas del carrito (Fase 2)

| Tabla | Migración | Notas |
|---|---|---|
| `carritos` | `2026_09_25_000005_create_carritos_table.php` | `user_id` nullable, `session_id` (cookie de invitado de 30 días), `expira_en`. |
| `items_carrito` | `2026_09_25_000006_create_items_carrito_table.php` | `carrito_id`, `producto_id`, `cantidad`, `precio_unitario_centimos` (snapshot, se recalcula al pagar). `unique(carrito_id, producto_id)`. |

### Tablas de pedidos y stock (Fase 3-4)

| Tabla | Migración | Notas |
|---|---|---|
| `pedidos` | `2026_09_25_000007_create_pedidos_table.php` + `2026_09_25_000010_add_respuesta_to_pedidos_table.php` | Datos de cliente/comprobante/entrega + `subtotal_centimos`, `costo_envio_centimos`, `descuento_centimos`, `total_centimos`, `estado_pago` (reusa `EstadoPago`), `estado_envio` (`EstadoEnvio`), `izipay_order_id` único, `transaction_uuid`, `card_brand`, `card_masked_pan`, `respuesta` (json). **Coexiste con `pagos`, no lo reemplaza.** |
| `items_pedido` | `2026_09_25_000008_create_items_pedido_table.php` | Snapshot inmutable: `sku`, `nombre`, `precio_unitario_centimos`, `cantidad`, `subtotal_centimos`. `producto_id` nullable (por si el producto se desactiva). |
| `movimientos_stock` | `2026_09_25_000009_create_movimientos_stock_table.php` | Bitácora: `producto_id`, `tipo` (reserva/salida/liberacion/entrada/ajuste), `cantidad` (con signo), `referencia` (código de pedido), `user_id` nullable (ajuste manual admin). |

### Relaciones clave

- `pagos.producto` y (antes) los pedidos NO usan FK hacia `productos` para el campo histórico — es deliberado, para que el historial sobreviva si el producto se desactiva/renombra.
- `items_pedido` SÍ tiene un `producto_id` (nullable) además del snapshot — permite referencia rápida sin depender de que el producto siga activo.
- `movimientos_stock.referencia` no es una FK — guarda el código de pedido como texto, se relaciona por convención (`Pedido::movimientosStock()` usa `referencia`↔`codigo`).

---

## 5. RUTAS Y ENDPOINTS (desde `routes/web.php`, confirmado con `php artisan route:list`)

| Método | URL | Controlador@método | Notas |
|---|---|---|---|
| GET | `/`, `/nosotros`, `/contacto` | `PageController` | Institucional |
| GET | `/servicio-{slug}` | `ServicioController@show` | Detalle de servicio |
| GET | `/productos` | `IzipayController@index` | Catálogo público (usa `App\Support\Producto`) |
| GET | `/carrito` | `CarritoController@index` | Vista del carrito |
| GET | `/carrito/resumen` | `CarritoController@mostrar` | JSON del carrito actual |
| POST | `/carrito/items` | `CarritoController@agregar` | Agregar producto (throttle 60/min) |
| PATCH/DELETE | `/carrito/items/{item}` | `CarritoController@actualizar` / `eliminar` | Requiere ser dueño del carrito (cookie) |
| GET | `/checkout` | `CheckoutController@index` | Redirige a `/carrito` si está vacío |
| POST | `/checkout` | `CheckoutController@crear` | Crea el pedido + formToken (throttle `izipay`) |
| POST | `/checkout/validar` | `CheckoutController@validar` | Retorno del navegador (nunca confirma pago) |
| POST | `/checkout/ipn` | `CheckoutController@ipn` | IPN de Izipay para `pedidos`, CSRF exento |
| POST | `/izipay/form-token` \| `/validar` \| `/ipn` | `IzipayController` | Flujo VIEJO, sigue activo para `pagos` |
| GET/POST | `/admin/login` | `Admin\LoginController` | Throttle `admin-login` |
| GET | `/admin/dashboard` (+`/exportar`) | `Admin\DashboardController` | Solo `pagos` — **no muestra `pedidos`** |
| GET/POST/PUT/PATCH | `/admin/productos/*` | `Admin\ProductosController` | CRUD (sin destroy); el form **no expone** categoría/marca/SKU/stock/imágenes |

---

## 6. TESTS (`tests/Feature/`)

| Archivo | Cubre |
|---|---|
| `IzipayCheckoutTest.php` | Flujo viejo completo: formToken, validar, IPN, firma, idempotencia, rate limiting, comando de expiración |
| `IzipayConciliacionTest.php` | Comando de conciliación de pagos |
| `AdminLoginTest.php`, `AdminDashboardTest.php`, `AdminProductosTest.php` | Panel admin (login, dashboard, CRUD de productos actual) |
| `CarritoTest.php` | Carrito: agregar/actualizar/eliminar, tope de stock, aislamiento entre carritos, render de vistas |
| `CheckoutTest.php` | Checkout nuevo: precio server-side, reserva de stock, liberación si Izipay falla, IPN confirma/libera stock, idempotencia, DNI/RUC, comando `pedidos:liberar-reservas-expiradas` |
| `ProxiesConfiablesTest.php` | Confianza de `X-Forwarded-For` |

**119 tests, 0 fallando** (confirmado con `php artisan test` el 2026-09-25).

---

## 7. AUTENTICACIÓN Y ROLES

Sin cambios respecto a antes: guard `web` estándar, **sin roles/permisos ni Policies** (`app/Policies` no existe). Cualquier fila de `users` autenticada puede administrar todo. El carrito y el checkout son de **invitado** (`user_id` nullable en `carritos`/`pedidos`, identificado por cookie `carrito_session`, no requieren login).

---

## 8. INTEGRACIÓN IZIPAY — ESTADO REAL (verificado con dinero real en producción)

- **Confirmado funcionando end-to-end**: checkout → formToken → PopIn (Krypton, español) → captura → depósito real en la cuenta del comercio (probado con S/1.00, capturado y conciliado).
- **Yape/Plin/QR**: el código ya envía `identityType`/`identityCode`/`country` (patrón del ejemplo oficial `izipay-pe/PopIn-PaymentForm-Laravel`), pero **no está confirmado si estos medios están activados** en la cuenta de comercio — pendiente de respuesta de soporte Izipay.
- **Comisión real**: no confirmada por documentación pública; pendiente de respuesta de soporte Izipay.
- **`metodo_pago`**: ni `pagos` ni `pedidos` distinguen tarjeta/Yape/Plin todavía (ver Problemas §11).
- **Modo de captura**: confirmado en Back Office como automático, 0 días (captura la misma tarde/noche).

---

## 9. PROBLEMAS DETECTADOS (auditoría 2026-09-25)

Ver el detalle completo con prioridad/esfuerzo en el reporte de auditoría de esta misma fecha (mensaje de Claude en la conversación del proyecto). Resumen:

### 🔴 Bloqueante para operar la tienda
1. El panel admin no muestra `pedidos` en ningún lado.
2. El form de productos no permite cargar categoría/marca/SKU/stock/imágenes.
3. Sin checkbox de términos y condiciones en checkout.
4. Sin email de confirmación al cliente ni aviso al admin.

### 🟠 Importante
5. `metodo_pago` no se guarda en `pagos` ni `pedidos`.
6. Costo de envío hardcodeado a 0, sin flag de configuración.
7. `.env.example` desactualizado (era el genérico de Laravel).
8. `IZIPAY_TESTING.md` no cubre el flujo nuevo (`/checkout/*`, `pedidos`).
9. `public/storage` (symlink) nunca se creó.
10. No confirmado si `/checkout/ipn` está registrada en el Back Office de Izipay.

### 🟢 Mejora
11. Sin índice explícito en `pedidos.estado_pago`.
12. Sin ajuste manual de stock con motivo desde el admin.
13. Sin alerta de stock bajo en el dashboard.
14. `README.md` genérico de Laravel.
15. Fase 5 (filtros, buscador, página de producto individual, mini-cart) sin construir.

---

## 10. MAPA MENTAL PARA OTRO DESARROLLADOR

1. **Hay DOS flujos de pago Izipay coexistiendo a propósito**: `pagos`/`IzipayController` (viejo, 1 producto por pago) y `pedidos`/`CheckoutController` (nuevo, carrito multi-producto). No fusionarlos sin revisar `PedidoPagoService` y `PagoService` — son máquinas de estado independientes que comparten el enum `EstadoPago` pero no la tabla.
2. **El catálogo YA está en base de datos** (`productos`, `categorias`, `marcas`), no en `config/*.php`. La fachada `App\Support\Producto` sigue existiendo como capa de caché (60s) sobre el modelo Eloquent — no es el catálogo en sí.
3. **`IzipayService` es genérico y reusado por ambos flujos** — si necesitas tocar la llamada HTTP a Izipay o la verificación de firma, es un solo lugar.
4. **El precio SIEMPRE se recalcula en servidor** al pagar (`PedidoService::crearDesdeCarrito`), nunca se confía en el snapshot del carrito ni en nada del navegador.
5. **El stock se mueve solo a través de `StockService`** (reservar/confirmar/liberar), con bitácora en `movimientos_stock`. No modifiques `productos.stock`/`stock_reservado` directamente desde otro lugar.
6. **El panel admin todavía no sabe que existen `pedidos`** — si te piden ver un pedido nuevo, hoy solo se puede por Tinker o consulta directa a BD.
7. **Frontend público es Bootstrap + JS vanilla a propósito** (decisión explícita para no migrar todo el sitio a Tailwind/Alpine solo por el e-commerce) — el dashboard admin nuevo sí usa Tailwind/Alpine, son dos sistemas de diseño conviviendo.
8. **Tests son PHPUnit clásico** (no Pest): `extends TestCase`, `use RefreshDatabase`, métodos `test_snake_case_en_espanol()`.

---

*Fin del informe.*
