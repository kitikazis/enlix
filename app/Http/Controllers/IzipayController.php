<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoPago;
use App\Models\Pago;
use App\Services\IzipayService;
use App\Services\PagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Support\Producto;

/**
 * Tienda (catálogo) + pago con Izipay (PopIn / Krypton client).
 *
 * - index(): muestra el catálogo. Agregar al carrito vive en
 *   CarritoController; el pago (formToken/validar/ipn de aquí abajo) sigue
 *   siendo el flujo de 1 producto por pago, ya en producción - el carrito
 *   nuevo (tabla `pedidos`) tendrá su propia integración con Izipay más
 *   adelante, sin tocar esta.
 * - formToken(): crea el formToken (el monto SIEMPRE sale del servidor)
 *   y registra el pago como 'pendiente'.
 * - validar(): retorno del navegador tras el PopIn (KR.onSubmit). Sirve
 *   para la experiencia de usuario, pero también exige firma válida.
 * - ipn(): notificación servidor-servidor de Izipay. Es la fuente de
 *   verdad; nunca crea filas nuevas (ver PagoService).
 */
class IzipayController extends Controller
{
    private const MENSAJE_GENERICO = 'No se pudo procesar el pago. Intenta nuevamente.';

    private const MENSAJE_VERIFICANDO = 'Estamos confirmando tu pago. En unos minutos verás el resultado.';

    public function index(): View
    {
        return view('productos', [
            'titulo' => 'Productos - Enlix',
            'current' => 'productos',
            'productos' => Producto::items(),
        ]);
    }

    public function formToken(Request $request, IzipayService $izipay): JsonResponse
    {
        $datos = $request->validate([
            'producto' => ['required', 'string', 'regex:/^[a-z0-9-]+$/'],
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email:rfc'],
            'phone_number' => ['required', 'string', 'regex:/^[0-9+ ]{6,20}$/'],
            // DNI (u otro documento de identidad): Izipay lo usa para habilitar
            // medios de pago adicionales (Yape, Plin, QR) ademas de tarjeta.
            'identity_code' => ['required', 'string', 'regex:/^[A-Za-z0-9]{6,15}$/'],
        ]);

        $producto = Producto::find($datos['producto']);

        // find() SI devuelve productos inactivos (un pago ya en curso debe
        // poder validarse igual), pero no se puede INICIAR una compra nueva
        // de algo que el admin desactivó, aunque alguien adivine el slug.
        if ($producto === null || ! $producto['activo']) {
            return response()->json(['ok' => false, 'mensaje' => 'Producto no encontrado.'], 404);
        }

        // El monto SIEMPRE se toma del servidor, nunca del cliente.
        $monto = (int) $producto['precio_centimos'];
        $orderId = 'ENX-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));

        $cliente = [
            'first_name' => strip_tags($datos['first_name']),
            'last_name' => strip_tags($datos['last_name']),
            'email' => $datos['email'],
            'phone_number' => $datos['phone_number'],
            'identity_code' => strtoupper($datos['identity_code']),
            // Confiable solo porque fuera de 'local' no se confía en
            // X-Forwarded-For (ver bootstrap/app.php): aquí llega REMOTE_ADDR.
            'ip' => $request->ip(),
        ];

        $resultado = $izipay->crearFormToken($monto, $orderId, $cliente);

        if (! $resultado['ok']) {
            // El detalle tecnico ya quedo en el log (IzipayService); al usuario, mensaje generico.
            return response()->json(['ok' => false, 'mensaje' => self::MENSAJE_GENERICO], 422);
        }

        try {
            Pago::create([
                'producto' => $producto['slug'],
                'email' => $datos['email'],
                'monto' => $monto,
                'moneda' => config('izipay.currency', 'PEN'),
                'izipay_order_id' => $orderId,
                'estado' => EstadoPago::Pendiente,
            ]);
        } catch (\Throwable $e) {
            // El formToken ya se genero: si esto falla, el IPN/validar no podra
            // encontrar la fila (PagoService nunca crea filas, ver su docblock).
            // Se prioriza cerrar esa via de creacion forjada de pagos sobre este
            // caso extremo; se deja constancia en el log para monitoreo.
            Log::error('Izipay: no se pudo registrar el pago pendiente en BD: '.$e->getMessage(), ['order_id' => $orderId]);
        }

        return response()->json([
            'ok' => true,
            'form_token' => $resultado['form_token'],
            'public_key' => $resultado['public_key'],
            'order_id' => $orderId,
        ]);
    }

    public function validar(Request $request, IzipayService $izipay, PagoService $pagos): JsonResponse
    {
        $datos = $request->validate([
            'kr-answer' => ['required', 'string'],
            'kr-hash' => ['required', 'string'],
            'kr-hash-algorithm' => ['required', 'string'],
            'kr-hash-key' => ['required', 'string'],
        ]);

        if ($datos['kr-hash-algorithm'] !== 'sha256_hmac' || $datos['kr-hash-key'] !== 'sha256_hmac') {
            Log::warning('Izipay validar: algoritmo/llave de hash inesperados');

            return response()->json(['ok' => false, 'mensaje' => self::MENSAJE_GENERICO], 422);
        }

        if (! $izipay->verificarFirma($datos['kr-answer'], $datos['kr-hash'], 'sha256_hmac')) {
            Log::warning('Izipay validar: firma invalida');

            return response()->json(['ok' => false, 'mensaje' => self::MENSAJE_GENERICO], 422);
        }

        $answer = json_decode($datos['kr-answer'], true);

        if (! is_array($answer)) {
            return response()->json(['ok' => false, 'mensaje' => self::MENSAJE_GENERICO], 422);
        }

        $resultado = $pagos->registrar($answer, PagoService::ORIGEN_VALIDAR);

        if (! $resultado['ok'] || ! ($resultado['procesado'] ?? false)) {
            return response()->json([
                'ok' => false,
                'pendiente' => true,
                'mensaje' => self::MENSAJE_VERIFICANDO,
            ], 202);
        }

        // El retorno del navegador nunca confirma el cobro: como mucho deja el
        // pago en verificación a la espera del IPN (ver PagoService).
        return match ($resultado['estado']) {
            EstadoPago::Pagado => response()->json([
                'ok' => true,
                'mensaje' => '¡Pago realizado con éxito!',
            ]),
            EstadoPago::EnVerificacion, EstadoPago::Pendiente => response()->json([
                'ok' => false,
                'pendiente' => true,
                'mensaje' => self::MENSAJE_VERIFICANDO,
            ], 202),
            default => response()->json(['ok' => false, 'mensaje' => self::MENSAJE_GENERICO], 422),
        };
    }

    /**
     * Notificación instantánea de pago (servidor-servidor). Es la fuente de
     * verdad; el navegador (validar()) solo sirve para la experiencia de
     * usuario. Siempre responde 200 con el texto que Izipay espera, para
     * que no reintente indefinidamente ante datos que no podemos procesar.
     */
    public function ipn(Request $request, IzipayService $izipay, PagoService $pagos): Response
    {
        $krAnswer = (string) $request->input('kr-answer', '');
        $krHash = (string) $request->input('kr-hash', '');
        $krHashAlgorithm = (string) $request->input('kr-hash-algorithm', '');
        $krHashKey = (string) $request->input('kr-hash-key', '');

        if ($krAnswer === '' || $krHash === '' || $krHashAlgorithm !== 'sha256_hmac' || $krHashKey !== 'password') {
            Log::warning('Izipay IPN: parametros incompletos o llave/algoritmo inesperados');

            return response('OK', 200);
        }

        if (! $izipay->verificarFirma($krAnswer, $krHash, 'password')) {
            Log::warning('Izipay IPN: firma invalida');

            return response('OK', 200);
        }

        $answer = json_decode($krAnswer, true);

        if (! is_array($answer)) {
            Log::warning('Izipay IPN: kr-answer no es JSON valido');

            return response('OK', 200);
        }

        $pagos->registrar($answer, PagoService::ORIGEN_IPN);

        $orderStatus = data_get($answer, 'orderStatus', 'DESCONOCIDO');

        return response('OK! OrderStatus is '.$orderStatus, 200);
    }
}
