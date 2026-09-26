<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoPago;
use App\Http\Requests\CheckoutCrearRequest;
use App\Services\CarritoService;
use App\Services\IzipayService;
use App\Services\PedidoPagoService;
use App\Services\PedidoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Checkout del carrito (multi-producto, tabla `pedidos`). Mismo patrón de
 * seguridad que IzipayController (firma HMAC, IPN como fuente de verdad,
 * nunca crea filas desde validar()/ipn(), el monto SIEMPRE sale del
 * servidor) pero coexistiendo con él, sin tocarlo - ver PedidoPagoService.
 *
 * - index(): muestra el checkout si el carrito tiene items.
 * - crear(): valida los datos del cliente, convierte el carrito en pedido
 *   (reserva stock) y genera el formToken.
 * - validar(): retorno del navegador tras el PopIn. Solo UX, no confirma pago.
 * - ipn(): notificación servidor-servidor. Fuente de verdad del pago.
 */
class CheckoutController extends Controller
{
    private const MENSAJE_GENERICO = 'No se pudo procesar el pago. Intenta nuevamente.';

    private const MENSAJE_VERIFICANDO = 'Estamos confirmando tu pago. En unos minutos verás el resultado.';

    public function __construct(private readonly CarritoService $carritos) {}

    public function index(): View|RedirectResponse
    {
        $resumen = $this->carritos->resumen();

        if ($resumen['cantidad_total'] < 1) {
            return redirect()->route('carrito.index');
        }

        return view('checkout', [
            'titulo' => 'Checkout - Enlix',
            'current' => 'productos',
            'resumen' => $resumen,
            'izipay_public_key' => config('izipay.public_key'),
            'izipay_js_client_url' => config('izipay.js_client_url'),
        ]);
    }

    public function crear(CheckoutCrearRequest $request, PedidoService $pedidos, IzipayService $izipay): JsonResponse
    {
        $datos = $request->validated();

        $carrito = $this->carritos->actual();

        if (! $carrito || $carrito->items()->count() === 0) {
            return response()->json(['ok' => false, 'mensaje' => 'Tu carrito está vacío.'], 422);
        }

        try {
            $pedido = $pedidos->crearDesdeCarrito($carrito, [
                'nombre_cliente' => trim($datos['first_name'].' '.$datos['last_name']),
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'tipo_documento' => $datos['tipo_documento'],
                'numero_documento' => $datos['numero_documento'],
                'tipo_comprobante' => $datos['tipo_comprobante'],
                'razon_social' => $datos['razon_social'] ?? null,
                'metodo_entrega' => $datos['metodo_entrega'],
                'direccion' => $datos['direccion'] ?? null,
                'distrito' => $datos['distrito'] ?? null,
                'ciudad' => $datos['ciudad'] ?? null,
                'referencia' => $datos['referencia'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'mensaje' => $e->getMessage()], 422);
        }

        $resultado = $izipay->crearFormToken($pedido->total_centimos, $pedido->codigo, [
            'first_name' => strip_tags($datos['first_name']),
            'last_name' => strip_tags($datos['last_name']),
            'email' => $datos['email'],
            'phone_number' => $datos['telefono'],
            'identity_code' => strtoupper($datos['numero_documento']),
            'ip' => $request->ip(),
        ], route('checkout.ipn'));

        if (! $resultado['ok']) {
            // No se deja el pedido en 'pendiente' con stock reservado para
            // siempre: si Izipay ni siquiera pudo crear el formToken, se
            // libera la reserva ahora mismo.
            $pedidos->cancelar($pedido);

            return response()->json(['ok' => false, 'mensaje' => self::MENSAJE_GENERICO], 422);
        }

        $pedido->update(['izipay_order_id' => $pedido->codigo]);

        // El carrito se vacía recién aquí (no dentro de PedidoService): si
        // Izipay hubiera fallado arriba, el cliente conserva su carrito
        // intacto para reintentar sin tener que armarlo de nuevo.
        $carrito->items()->delete();

        return response()->json([
            'ok' => true,
            'form_token' => $resultado['form_token'],
            'public_key' => $resultado['public_key'],
        ]);
    }

    public function validar(Request $request, IzipayService $izipay, PedidoPagoService $pedidos): JsonResponse
    {
        $datos = $request->validate([
            'kr-answer' => ['required', 'string'],
            'kr-hash' => ['required', 'string'],
            'kr-hash-algorithm' => ['required', 'string'],
            'kr-hash-key' => ['required', 'string'],
        ]);

        if ($datos['kr-hash-algorithm'] !== 'sha256_hmac' || $datos['kr-hash-key'] !== 'sha256_hmac') {
            Log::warning('Checkout validar: algoritmo/llave de hash inesperados');

            return response()->json(['ok' => false, 'mensaje' => self::MENSAJE_GENERICO], 422);
        }

        if (! $izipay->verificarFirma($datos['kr-answer'], $datos['kr-hash'], 'sha256_hmac')) {
            Log::warning('Checkout validar: firma invalida');

            return response()->json(['ok' => false, 'mensaje' => self::MENSAJE_GENERICO], 422);
        }

        $answer = json_decode($datos['kr-answer'], true);

        if (! is_array($answer)) {
            return response()->json(['ok' => false, 'mensaje' => self::MENSAJE_GENERICO], 422);
        }

        $resultado = $pedidos->registrar($answer, PedidoPagoService::ORIGEN_VALIDAR);

        if (! $resultado['ok'] || ! ($resultado['procesado'] ?? false)) {
            return response()->json([
                'ok' => false,
                'pendiente' => true,
                'mensaje' => self::MENSAJE_VERIFICANDO,
            ], 202);
        }

        // El retorno del navegador nunca confirma el cobro: como mucho deja
        // el pedido en verificación a la espera del IPN (ver PedidoPagoService).
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
     * usuario. Siempre responde 200 con el texto que Izipay espera, para que
     * no reintente indefinidamente ante datos que no podemos procesar.
     */
    public function ipn(Request $request, IzipayService $izipay, PedidoPagoService $pedidos): Response
    {
        $krAnswer = (string) $request->input('kr-answer', '');
        $krHash = (string) $request->input('kr-hash', '');
        $krHashAlgorithm = (string) $request->input('kr-hash-algorithm', '');
        $krHashKey = (string) $request->input('kr-hash-key', '');

        if ($krAnswer === '' || $krHash === '' || $krHashAlgorithm !== 'sha256_hmac' || $krHashKey !== 'password') {
            Log::warning('Checkout IPN: parametros incompletos o llave/algoritmo inesperados');

            return response('OK', 200);
        }

        if (! $izipay->verificarFirma($krAnswer, $krHash, 'password')) {
            Log::warning('Checkout IPN: firma invalida');

            return response('OK', 200);
        }

        $answer = json_decode($krAnswer, true);

        if (! is_array($answer)) {
            Log::warning('Checkout IPN: kr-answer no es JSON valido');

            return response('OK', 200);
        }

        $resultado = $pedidos->registrar($answer, PedidoPagoService::ORIGEN_IPN);

        // No se loguea el kr-answer completo (trae nombre/email/telefono del
        // cliente): solo los campos de estado, suficientes para diagnosticar
        // un mapeo de estado sin volcar datos personales al log.
        Log::info('Checkout IPN: notificacion recibida', [
            'izipay_order_id' => data_get($answer, 'orderDetails.orderId'),
            'order_status' => data_get($answer, 'orderStatus'),
            'detailed_status' => data_get($answer, 'transactions.0.detailedStatus'),
            'procesado' => $resultado['procesado'] ?? false,
            'estado_resultante' => ($resultado['estado'] ?? null)?->value,
            'motivo' => $resultado['motivo'] ?? null,
        ]);

        $orderStatus = data_get($answer, 'orderStatus', 'DESCONOCIDO');

        return response('OK! OrderStatus is '.$orderStatus, 200);
    }
}
