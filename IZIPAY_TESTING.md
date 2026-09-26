# Checklist de certificación Izipay — Enlix

> Objetivo: cubrir los 4 puntos que pide Izipay para habilitar el cobro real:
> integración, pruebas en TEST, pruebas en PRODUCCIÓN e IPN.
>
> Estado del código (auditado en `app/Services/IzipayService.php`,
> `app/Http/Controllers/IzipayController.php`, `app/Services/PagoService.php`,
> `tests/Feature/IzipayCheckoutTest.php`): la integración y el IPN ya están
> implementados y cubiertos por 12 tests automatizados. Lo que falta es
> **ejecutar** las pruebas reales contra Izipay (TEST y PRODUCCIÓN) y
> **registrar la URL del IPN** en su Back Office — eso no se puede hacer
> desde el código, requiere credenciales reales y acceso al panel de Izipay.
>
> ⚠️ **Este documento describe el flujo VIEJO** (`/productos` → tarjeta
> directa → tabla `pagos`, 1 producto por pago). Desde la Fase 4 del
> e-commerce existe un flujo NUEVO y paralelo (`/checkout`, carrito
> multi-producto, tabla `pedidos`) que reutiliza el mismo `IzipayService`
> pero tiene su propio IPN y sus propias credenciales de Back Office
> (las mismas de Izipay, no hay una cuenta distinta) — ver sección 5.

---

## 1. Integración de la pasarela de pagos — ✅ en código

- `IzipayService::crearFormToken()` crea el `formToken` (API REST V4,
  `Charge/CreatePayment`) con el monto tomado **siempre del servidor**
  (`config/productos.php`, nunca del request del navegador).
- El checkout usa el cliente Krypton (PopIn) — la tarjeta nunca toca el
  servidor de Enlix.
- `IzipayService::verificarFirma()` valida el HMAC-SHA256 de toda respuesta
  (`kr-answer`/`kr-hash`), usando la llave correcta según el canal
  (`password` para IPN, `sha256_hmac` para el retorno del navegador).
- `PagoService::registrar()` es idempotente y nunca crea filas nuevas desde
  `validar()`/`ipn()` (evita fabricar pagos con un `orderId` inventado).

No se requiere ningún cambio de código para este punto.

---

## 2. Pruebas en ambiente TEST

### 2.1 Credenciales

En el Back Office de Izipay: **Configuración → Tienda → Claves de API**,
copia las claves de la pestaña **TEST** y complétalas en tu `.env` local
(nunca se suben al repo, `.env` está en `.gitignore`):

```
IZIPAY_USERNAME=       # shopId (modo test)
IZIPAY_PASSWORD=       # clave de test
IZIPAY_PUBLIC_KEY=     # public key de test
IZIPAY_SHA256_KEY=     # llave HMAC SHA-256 de test
```

`IZIPAY_BASE_URL` no cambia entre test y producción (mismo endpoint,
`api.micuentaweb.pe`); el ambiente lo determina el juego de credenciales.

### 2.2 Levantar el sitio y exponerlo a internet (necesario para el IPN)

El IPN es una llamada servidor-a-servidor desde Izipay hacia tu app, así
que `localhost` no es alcanzable — hay que exponerlo con un túnel:

```bash
php artisan serve            # sirve en http://127.0.0.1:8000
ngrok http 8000               # te da una URL pública https://xxxx.ngrok-free.app
```

Usa esa URL de ngrok como `APP_URL` en `.env` mientras pruebas, y como base
para la URL del IPN (paso 2.3).

### 2.3 Registrar la URL del IPN en el Back Office (paso que se olvida seguido)

**Configuración → Reglas de notificaciones** (el nombre exacto puede variar
según la versión del Back Office; en plataformas Izipay/Lyra suele llamarse
"Notification rules" o "Reglas de notificación IPN"):

1. Crea/edita la regla para el evento de pago (p. ej. "Notificación de
   validación del pago" / IPN).
2. URL: `https://xxxx.ngrok-free.app/izipay/ipn`
3. **Marca la casilla que habilita la regla también en modo TEST**
   (suele estar desmarcada por defecto y es la causa #1 de "el IPN nunca me
   llega" en pruebas — sin ella, Izipay solo llama al IPN en producción).
4. Guarda y, si el panel lo ofrece, usa el botón "Probar la URL" /
   "Test the URL" para confirmar que responde `200`.

### 2.4 Tarjetas de prueba

Usa **únicamente** las tarjetas de test oficiales que Izipay entrega en su
Back Office o documentación para comerciante (sección "Tarjetas de test" /
"Test cards"). No inventes números de tarjeta: cada cuenta de test suele
tener sus propios PAN de prueba (uno que aprueba, uno que rechaza, uno con
fondos insuficientes, etc.).

### 2.5 Casos a ejecutar manualmente (con navegador real)

1. **Compra exitosa**: `/productos` → completar datos → PopIn → tarjeta de
   test "aprobada". Verificar:
   - El navegador muestra "¡Pago realizado con éxito!" (`validar()`).
   - `php artisan tinker` → `App\Models\Pago::latest()->first()` → `estado`
     = `pagado`, `transaction_uuid`, `card_brand`, `card_masked_pan`
     completos.
   - En los logs (`storage/logs/laravel.log`) aparece `Izipay: pago
     registrado` — y confirmar que **no** aparecen `form_token`, `kr-hash`
     ni las claves de `.env` en ningún log (esto ya lo cubre
     `test_logs_no_contienen_form_token_ni_kr_hash`, pero vale confirmarlo
     también en una corrida real).
2. **Compra rechazada**: tarjeta de test "rechazada" → el pago debe quedar
   en `rechazado`, nunca en `pagado`.
3. **Confirmar que el IPN llegó de verdad** (no solo el retorno del
   navegador): revisa el log de ngrok (`http://127.0.0.1:4040`) y confirma
   que Izipay hizo un `POST /izipay/ipn` independiente del `POST
   /izipay/validar` del navegador. Ambos deben dejar el pago en el mismo
   estado final.
4. **Abandonar el checkout** (cerrar el PopIn sin pagar) y, más de 24h
   después (o ajustando `created_at` a mano en BD para no esperar), correr
   `php artisan izipay:expirar-pendientes` y confirmar que pasa a
   `expirado`.

Los casos de manipulación (monto/moneda alterados, firma inválida, `kr-hash-key`
incorrecto, IPN repetido) ya están cubiertos por
`tests/Feature/IzipayCheckoutTest.php` — correr `php artisan test
--filter=IzipayCheckoutTest` y confirmar que los 12 tests pasan es parte de
la evidencia de "pruebas en TEST".

---

## 3. Pruebas en ambiente PRODUCCIÓN

⚠️ Aquí se mueve dinero real. Usa el monto más bajo posible y reembolsa la
transacción desde el Back Office al terminar.

### 3.1 Requisitos previos

- Izipay debe haber **activado tu cuenta de producción** (normalmente pasan
  a este paso después de revisar que TEST funcionó — puede requerir enviar
  evidencia de la sección 2).
- Dominio real con HTTPS válido (no ngrok) desplegado — Izipay no
  garantiza reintentos de IPN indefinidos contra túneles temporales.

### 3.2 Configuración

En `.env` del **servidor de producción**:

```
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true

IZIPAY_USERNAME=       # shopId de producción
IZIPAY_PASSWORD=       # clave de producción
IZIPAY_PUBLIC_KEY=     # public key de producción
IZIPAY_SHA256_KEY=     # llave HMAC de producción
```

Nunca reutilices credenciales de test en producción ni viceversa — Izipay
las emite por separado y son distintos ambientes lógicos aunque el
`base_url` sea el mismo.

### 3.3 Registrar el IPN de producción

Repite el paso 2.3 pero con la URL real:
`https://tudominio.pe/izipay/ipn` (sin la casilla de "modo test").

### 3.4 Transacción real de prueba

1. Compra un producto con una tarjeta real (tuya), monto más bajo
   disponible.
2. Verifica en BD que el `Pago` quedó `pagado`, con `transaction_uuid` y
   `card_masked_pan` reales.
3. Verifica en los logs de producción que el IPN llegó y que no se filtró
   ningún secreto.
4. **Reembolsa** esa transacción desde el Back Office de Izipay
   (Producción → Transacciones → Reembolsar) para no quedarte con el
   cargo.

---

## 4. Implementación de IPN — ✅ en código, pendiente registrar la URL

`IzipayController::ipn()` (`app/Http/Controllers/IzipayController.php:154`):

- Exento de CSRF solo en esa ruta (`bootstrap/app.php`).
- Exige `kr-hash-algorithm = sha256_hmac` y `kr-hash-key = password`
  (distinto de la llave que usa el retorno del navegador).
- Verifica la firma HMAC antes de tocar la base de datos.
- Es la **fuente de verdad**: aunque el navegador nunca vuelva (usuario
  cierra la pestaña tras pagar), el IPN por sí solo marca el pago como
  `pagado`.
- Siempre responde `200 OK` (incluso ante datos inválidos) para que Izipay
  no reintente indefinidamente algo que nunca vamos a poder procesar.
- Es idempotente ante reintentos (`test_ipn_repetido_cinco_veces_deja_un_solo_registro_y_estado_estable`).

**Lo único que falta** es el paso operativo (no de código): registrar
`/izipay/ipn` en el Back Office de Izipay, para TEST (sección 2.3) y para
PRODUCCIÓN (sección 3.3).

---

## 5. Checkout nuevo (carrito, tabla `pedidos`)

`CheckoutController` (`/checkout`, `/checkout/validar`, `/checkout/ipn`) es
el flujo multi-producto. Reutiliza `IzipayService` tal cual (mismo
`Charge/CreatePayment`, misma verificación HMAC) pero registra el resultado
en `PedidoPagoService`/`pedidos`, no en `PagoService`/`pagos` — son dos
motores independientes que coexisten a propósito (ver `PROJECT_CONTEXT.md`).

### 5.1 IPN: ya no depende solo de la regla del Back Office

Desde el commit `fix: el checkout nuevo manda ipnTargetUrl explicito a
Izipay`, cada `Charge/CreatePayment` del checkout incluye
`ipnTargetUrl` apuntando a `route('checkout.ipn')` (URL absoluta,
calculada del `APP_URL` de ese momento). En teoría esto le dice a Izipay
a dónde mandar el IPN **de esa orden puntual**, sin depender de que
alguien haya registrado una regla global.

**Aun así, registra `/checkout/ipn` como regla en el Back Office** (mismo
procedimiento que la sección 2.3/3.3, pero con esta URL) como respaldo:
`ipnTargetUrl` es un parámetro documentado por Lyra para la Transaction de
la API V4, pero no hay una prueba real todavía que confirme que Izipay lo
respeta tal cual en esta cuenta — si lo ignorara, sin la regla global el
checkout nuevo se quedaría sin IPN.

- TEST: `https://xxxx.ngrok-free.app/checkout/ipn`
- PRODUCCIÓN: `https://enlix.pe/checkout/ipn`

### 5.2 Diferencias a la hora de probar

- El checkout nuevo exige el checkbox de términos y condiciones
  (`/terminos`) antes de generar el formToken.
- El monto que se manda a Izipay incluye el costo de envío si
  `metodo_entrega = envio` y `config('tienda.envio.modo') = fijo` (por
  defecto es `gratis`, no cobra nada extra) — ver `config/tienda.php`.
- Al confirmarse el pago (IPN, nunca el retorno del navegador) se
  descuenta stock real y se mandan dos correos: confirmación al cliente y
  aviso a `ADMIN_EMAIL` (si está configurado) — revisa
  `storage/logs/laravel.log` en local (`MAIL_MAILER=log`) para verlos sin
  necesitar SMTP real.
- El pedido queda visible en `/admin/pedidos` (listado y detalle), no en
  el dashboard de pagos.

Cubierto por `tests/Feature/CheckoutTest.php` (formToken con precio del
servidor, `ipnTargetUrl`, reserva/liberación de stock, IPN idempotente,
DNI/RUC, términos, envío gratis/fijo) — correr `php artisan test
--filter=CheckoutTest`.

---

## Checklist final para responder a Izipay

- [ ] Integración de la pasarela — código listo (`IzipayService`,
      `IzipayController`, `PagoService`).
- [ ] Pruebas en TEST ejecutadas (pago aprobado, pago rechazado, IPN
      confirmado por log/ngrok) — sección 2.
- [ ] Pruebas en PRODUCCIÓN ejecutadas (transacción real + reembolso) —
      sección 3.
- [ ] IPN registrado en Back Office (TEST y PRODUCCIÓN) y confirmado con
      el botón "probar URL" del panel, o con una notificación real
      recibida — sección 4.
- [ ] `/checkout/ipn` registrado igual que `/izipay/ipn` (TEST y
      PRODUCCIÓN) — sección 5.1, aunque `ipnTargetUrl` ya lo manda por su
      cuenta.
