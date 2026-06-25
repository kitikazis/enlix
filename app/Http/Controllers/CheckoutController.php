<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Services\CulqiService;
use App\Support\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Tienda + pago con Culqi.
 *
 * - index(): muestra los 3 productos y la llave pública de Culqi.
 * - pagar(): recibe el token de la tarjeta (generado en el navegador),
 *   toma el precio DEL SERVIDOR y crea el cargo en Culqi.
 */
class CheckoutController extends Controller
{
    public function index(): View
    {
        return view('productos', [
            'titulo'    => 'Productos - Enlix',
            'current'   => 'productos',
            'productos' => Producto::items(),
            'culqi_pk'  => config('culqi.public_key'),
        ]);
    }

    public function pagar(Request $request, CulqiService $culqi): JsonResponse
    {
        $datos = $request->validate([
            'token'    => ['required', 'string'],
            'producto' => ['required', 'string'],
            'email'    => ['required', 'email'],
        ]);

        $producto = Producto::find($datos['producto']);

        if ($producto === null) {
            return response()->json(['ok' => false, 'mensaje' => 'Producto no encontrado.'], 404);
        }

        // El monto SIEMPRE se toma del servidor, nunca del cliente.
        $monto = (int) $producto['precio_centimos'];

        $resultado = $culqi->crearCargo(
            sourceId: $datos['token'],
            montoCentimos: $monto,
            email: $datos['email'],
            metadata: [
                'producto' => $producto['slug'],
                'nombre'   => $producto['nombre'],
            ],
        );

        if (! $resultado['ok']) {
            Log::warning('Culqi: cargo rechazado', $resultado);

            $mensaje = data_get($resultado, 'data.user_message')
                ?? data_get($resultado, 'data.merchant_message')
                ?? 'No se pudo procesar el pago. Intenta con otra tarjeta.';

            return response()->json(['ok' => false, 'mensaje' => $mensaje], 422);
        }

        $cargo = $resultado['data'];

        // Registrar el pago (si la base de datos está disponible).
        try {
            Pago::create([
                'producto'        => $producto['slug'],
                'email'           => $datos['email'],
                'monto'           => $monto,
                'moneda'          => config('culqi.currency', 'PEN'),
                'culqi_charge_id' => $cargo['id'] ?? null,
                'estado'          => 'pagado',
                'respuesta'       => $cargo,
            ]);
        } catch (\Throwable $e) {
            // El cargo ya se realizó: no rompemos la respuesta por un fallo de BD.
            Log::warning('Culqi: no se pudo guardar el pago en BD: '.$e->getMessage());
        }

        return response()->json([
            'ok'        => true,
            'mensaje'   => '¡Pago realizado con éxito! Te enviaremos los detalles a tu correo.',
            'charge_id' => $cargo['id'] ?? null,
        ]);
    }

    /**
     * Crea una orden de Culqi para pagar con billeteras / banca móvil / Cuotéalo.
     * Devuelve el order_id que el navegador pasa a Culqi.settings({ order }).
     */
    public function crearOrden(Request $request, CulqiService $culqi): JsonResponse
    {
        $datos = $request->validate([
            'producto'     => ['required', 'string'],
            'first_name'   => ['required', 'string', 'max:50'],
            'last_name'    => ['required', 'string', 'max:50'],
            'email'        => ['required', 'email'],
            'phone_number' => ['required', 'string', 'max:20'],
        ]);

        $producto = Producto::find($datos['producto']);

        if ($producto === null) {
            return response()->json(['ok' => false, 'mensaje' => 'Producto no encontrado.'], 404);
        }

        $monto = (int) $producto['precio_centimos'];

        $resultado = $culqi->crearOrden(
            montoCentimos: $monto,
            descripcion: $producto['nombre'],
            orderNumber: 'ENX-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
            cliente: [
                'first_name'   => $datos['first_name'],
                'last_name'    => $datos['last_name'],
                'email'        => $datos['email'],
                'phone_number' => $datos['phone_number'],
            ],
        );

        if (! $resultado['ok']) {
            Log::warning('Culqi: no se pudo crear la orden', $resultado);

            $mensaje = data_get($resultado, 'data.user_message')
                ?? data_get($resultado, 'data.merchant_message')
                ?? 'No se pudo iniciar el pago.';

            return response()->json(['ok' => false, 'mensaje' => $mensaje], 422);
        }

        $orderId = $resultado['data']['id'] ?? null;

        // Registro preliminar del pedido (si hay base de datos). El webhook o la
        // verificación lo marcarán como 'pagado' usando este mismo order_id.
        try {
            Pago::updateOrCreate(
                ['culqi_charge_id' => $orderId],
                [
                    'producto' => $producto['slug'],
                    'email'    => $datos['email'],
                    'monto'    => $monto,
                    'moneda'   => config('culqi.currency', 'PEN'),
                    'estado'   => 'pendiente',
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Culqi: no se pudo registrar el pedido en BD: '.$e->getMessage());
        }

        return response()->json([
            'ok'       => true,
            'order_id' => $orderId,
            'monto'    => $monto,
        ]);
    }

    /**
     * Verifica el estado de una orden tras el pago (billeteras / banca móvil / Cuotéalo).
     * Si la orden está 'paid' registra el pago y confirma al cliente.
     */
    public function verificarOrden(Request $request, CulqiService $culqi): JsonResponse
    {
        $datos = $request->validate([
            'order_id' => ['required', 'string'],
            'producto' => ['required', 'string'],
        ]);

        $producto = Producto::find($datos['producto']);

        if ($producto === null) {
            return response()->json(['ok' => false, 'mensaje' => 'Producto no encontrado.'], 404);
        }

        $resultado = $culqi->obtenerOrden($datos['order_id']);

        if (! $resultado['ok']) {
            Log::warning('Culqi: no se pudo verificar la orden', $resultado);

            return response()->json(['ok' => false, 'mensaje' => 'No se pudo verificar la orden.'], 422);
        }

        $orden  = $resultado['data'] ?? [];
        $estado = $orden['state'] ?? null;

        if ($estado !== 'paid') {
            // created / pending: el pago aún no se completó (p. ej. métodos diferidos).
            return response()->json([
                'ok'        => false,
                'pendiente' => true,
                'mensaje'   => 'Tu pago está en proceso. Te confirmaremos por correo cuando se complete.',
            ], 202);
        }

        $this->registrarPagoOrden($orden, $producto['slug']);

        return response()->json([
            'ok'        => true,
            'mensaje'   => '¡Pago confirmado con éxito! Te enviaremos los detalles a tu correo.',
            'charge_id' => $orden['id'] ?? null,
        ]);
    }

    /**
     * Webhook de Culqi: confirma pagos diferidos (agentes y bodegas / PagoEfectivo).
     *
     * Por seguridad NO confiamos en el cuerpo recibido: re-consultamos la orden a
     * Culqi con la secret key y solo registramos el pago si confirma state==='paid'.
     * Así un webhook falsificado no puede marcar un pago como pagado.
     */
    public function webhook(Request $request, CulqiService $culqi): JsonResponse
    {
        $evento = $request->all();
        Log::info('Culqi webhook recibido', ['type' => $evento['type'] ?? null]);

        // Extraer el id de orden del evento (tolerante a variaciones de formato).
        $data    = $evento['data'] ?? $evento;
        $posible = $data['id'] ?? null;
        $orderId = (is_string($posible) && str_starts_with($posible, 'ord_'))
            ? $posible
            : ($data['order_id'] ?? null);

        // Si no es un evento de orden, respondemos 200 y salimos (nada que hacer).
        if (! $orderId) {
            return response()->json(['ok' => true]);
        }

        $resultado = $culqi->obtenerOrden($orderId);

        if (! $resultado['ok']) {
            Log::warning('Culqi webhook: no se pudo consultar la orden', ['order_id' => $orderId]);

            return response()->json(['ok' => false], 200);
        }

        $orden = $resultado['data'] ?? [];

        if (($orden['state'] ?? null) !== 'paid') {
            return response()->json(['ok' => true]); // aún no pagada
        }

        Log::info('Culqi webhook: orden pagada', ['order_id' => $orderId]);

        $this->registrarPagoOrden($orden);

        return response()->json(['ok' => true]);
    }

    /**
     * Marca como 'pagado' el registro de una orden (idempotente por order_id).
     * Reutilizado por la verificación síncrona y por el webhook.
     */
    private function registrarPagoOrden(array $orden, ?string $producto = null): void
    {
        try {
            $valores = [
                'email'     => data_get($orden, 'client_details.email', ''),
                'monto'     => (int) ($orden['amount'] ?? 0),
                'moneda'    => $orden['currency_code'] ?? config('culqi.currency', 'PEN'),
                'estado'    => 'pagado',
                'respuesta' => $orden,
            ];

            if ($producto !== null) {
                $valores['producto'] = $producto;
            }

            Pago::updateOrCreate(
                ['culqi_charge_id' => $orden['id'] ?? null],
                $valores
            );
        } catch (\Throwable $e) {
            Log::warning('Culqi: no se pudo guardar el pago (orden) en BD: '.$e->getMessage());
        }
    }
}
