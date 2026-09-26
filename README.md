# Enlix

Sitio web corporativo + tienda online de Enlix (Lima, Perú). Laravel 12,
renderizado en servidor con Blade, sin SPA ni API pública.

- **Institucional**: inicio, nosotros, contacto, catálogo de servicios de TI.
- **Tienda**: catálogo de productos (BD), carrito por cookie de invitado,
  checkout con pago por [Izipay](https://www.izipay.pe/) (cliente Krypton/PopIn).
- **Admin** (`/admin`): login, gestión de productos (categoría, marca, SKU,
  stock, imágenes), listado de pedidos, dashboard de pagos.

Para el detalle completo de arquitectura, base de datos, rutas y flujos,
ver **[PROJECT_CONTEXT.md](PROJECT_CONTEXT.md)**. Para certificar/probar la
integración con Izipay, ver **[IZIPAY_TESTING.md](IZIPAY_TESTING.md)**.

## Levantar el proyecto en local

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # DB_CONNECTION=sqlite por defecto
php artisan migrate
php artisan db:seed              # categorías, marcas y el producto de prueba
php artisan storage:link         # necesario para las imágenes de producto
```

Completa en `.env` las credenciales de Izipay en modo **TEST** (ver
`IZIPAY_TESTING.md`, sección 2.1) antes de probar el checkout.

Levantar el servidor:

```bash
php artisan serve
```

## Tests

```bash
php artisan test
```

PHPUnit (no Pest), `RefreshDatabase` contra SQLite en memoria. Cubre carrito,
stock, checkout/pedidos, el flujo viejo de pagos, panel admin y seguridad
(rate limiting, proxies confiables).

## Frontend

- Sitio público y admin CRUD de productos: Bootstrap 5 (CDN) + JS vanilla,
  sin build step.
- Dashboard admin (`/admin/dashboard`) y listado/detalle de pedidos:
  Tailwind v4 (Vite) + Alpine.js (CDN). Para compilar sus assets:

```bash
npm install
npm run dev    # o npm run build para producción
```

## Despliegue (cPanel)

No hay CI/CD automatizado. El flujo manual es: `git pull` en el servidor,
`composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`,
`php artisan view:clear` (o `config:cache`/`route:cache` si aplica), y
`php artisan storage:link` la primera vez. El cron de cPanel debe correr
`php artisan schedule:run` cada minuto (concilia pagos, expira pendientes y
libera reservas de stock abandonadas — ver `routes/console.php`).
