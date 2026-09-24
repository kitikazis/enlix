<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente del API REST V4 de Izipay (plataforma micuentaweb.pe).
 *
 * La tarjeta nunca pasa por nuestro servidor: el formulario Krypton la
 * captura directamente en el navegador (PopIn). Aquí solo se crea el
 * formToken (backend, con la secret key) y se verifica la firma HMAC de
 * las respuestas (kr-answer / kr-hash).
 */
class IzipayService
{
    /**
     * Crea un formToken para iniciar el pago (PopIn de Izipay).
     *
     * @param  array  $cliente  ['first_name','last_name','email','phone_number','identity_code','ip']
     * @return array{ok: bool, http: int, form_token: ?string, public_key: ?string}
     */
    public function crearFormToken(int $montoCentimos, string $orderId, array $cliente): array
    {
        $payload = [
            'amount' => $montoCentimos,
            'currency' => config('izipay.currency', 'PEN'),
            'orderId' => $orderId,
            'customer' => [
                'email' => $cliente['email'],
                'billingDetails' => [
                    'firstName' => $cliente['first_name'],
                    'lastName' => $cliente['last_name'],
                    'phoneNumber' => $cliente['phone_number'],
                    // Documento de identidad + pais: Izipay los usa para
                    // habilitar medios de pago adicionales (Yape, Plin, QR)
                    // ademas de tarjeta en el mismo PopIn.
                    'identityType' => 'DNI',
                    'identityCode' => $cliente['identity_code'],
                    'country' => 'PE',
                ],
            ],
        ];

        // La IP del comprador es una de las señales que pesan en el analizador
        // de riesgo de Izipay (ThreatMetrix). Solo se envía si es confiable:
        // con proxies no confiables, $request->ip() sería un valor que el
        // propio atacante elige, y mandarlo ensuciaría el antifraude.
        //
        // // TODO: verificar en la doc de micuentaweb.pe la ruta exacta del
        // // campo (se asume customer.extraDetails.ipAddress).
        if (! empty($cliente['ip'])) {
            $payload['customer']['extraDetails']['ipAddress'] = $cliente['ip'];
        }

        try {
            $response = Http::withBasicAuth(
                (string) config('izipay.username'),
                (string) config('izipay.password')
            )
                ->acceptJson()
                ->asJson()
                ->connectTimeout((int) config('izipay.connect_timeout'))
                ->timeout((int) config('izipay.timeout'))
                ->post($this->url('V4/Charge/CreatePayment'), $payload);
        } catch (ConnectionException) {
            return ['ok' => false, 'http' => 0, 'form_token' => null, 'public_key' => null];
        }

        if (! $response->successful()) {
            // Nunca se loguea el payload completo (podria variar en el futuro); solo el status HTTP.
            Log::warning('Izipay: fallo al crear formToken', ['http' => $response->status()]);

            return ['ok' => false, 'http' => $response->status(), 'form_token' => null, 'public_key' => null];
        }

        $data = $response->json();
        $formToken = data_get($data, 'answer.formToken');

        // Izipay responde HTTP 200 incluso con credenciales invalidas u otros
        // errores: el fallo viene dentro del body ("status":"ERROR"), no en
        // el codigo HTTP. Sin esta validacion, un formToken vacio se habria
        // reportado como exito.
        if (data_get($data, 'status') !== 'SUCCESS' || empty($formToken)) {
            Log::warning('Izipay: respuesta sin formToken', [
                'http' => $response->status(),
                'status' => data_get($data, 'status'),
                'error_code' => data_get($data, 'answer.errorCode'),
            ]);

            return ['ok' => false, 'http' => $response->status(), 'form_token' => null, 'public_key' => null];
        }

        return [
            'ok' => true,
            'http' => $response->status(),
            'form_token' => $formToken,
            'public_key' => data_get($data, 'answer.publicKey', config('izipay.public_key')),
        ];
    }

    /**
     * Consulta en Izipay el estado real de una orden ya creada.
     *
     * Se usa cuando no llegó el IPN (job de conciliación y expiración): antes
     * de dar por perdido un pago hay que preguntarle a la fuente de verdad.
     *
     * 'encontrada' => false significa que Izipay respondió pero no conoce esa
     * orden (el comprador nunca llegó a intentar pagar). 'ok' => false
     * significa que no se pudo consultar: en ese caso no se toca nada.
     *
     * // TODO: verificar en la doc de micuentaweb.pe el nombre exacto del
     * // servicio (V4/Order/Get) y el parámetro (orderId).
     *
     * @return array{ok: bool, encontrada: bool, answer: array<string, mixed>}
     */
    public function consultarOrden(string $orderId): array
    {
        try {
            $response = Http::withBasicAuth(
                (string) config('izipay.username'),
                (string) config('izipay.password')
            )
                ->acceptJson()
                ->asJson()
                ->connectTimeout((int) config('izipay.connect_timeout'))
                ->timeout((int) config('izipay.timeout'))
                ->post($this->url('V4/Order/Get'), ['orderId' => $orderId]);
        } catch (ConnectionException) {
            Log::warning('Izipay: no se pudo consultar la orden', ['izipay_order_id' => $orderId]);

            return ['ok' => false, 'encontrada' => false, 'answer' => []];
        }

        if (! $response->successful()) {
            Log::warning('Izipay: consulta de orden fallida', [
                'izipay_order_id' => $orderId,
                'http' => $response->status(),
            ]);

            return ['ok' => false, 'encontrada' => false, 'answer' => []];
        }

        $data = $response->json();

        if (data_get($data, 'status') !== 'SUCCESS') {
            // Izipay contestó, pero la orden no existe o no es consultable.
            Log::info('Izipay: la orden no existe en la pasarela', [
                'izipay_order_id' => $orderId,
                'error_code' => data_get($data, 'answer.errorCode'),
            ]);

            return ['ok' => true, 'encontrada' => false, 'answer' => []];
        }

        return ['ok' => true, 'encontrada' => true, 'answer' => (array) data_get($data, 'answer', [])];
    }

    private function url(string $servicio): string
    {
        return rtrim((string) config('izipay.base_url'), '/').'/api-payment/'.$servicio;
    }

    /**
     * Verifica la firma HMAC-SHA256 de un kr-answer.
     *
     * $krHashKey indica qué llave usar: 'password' (IPN, servidor-servidor)
     * o 'sha256_hmac' (retorno del navegador). Cualquier otro valor se
     * rechaza de inmediato.
     */
    public function verificarFirma(string $krAnswerRaw, string $krHash, string $krHashKey): bool
    {
        $llave = match ($krHashKey) {
            'password' => (string) config('izipay.password'),
            'sha256_hmac' => (string) config('izipay.sha256_key'),
            default => null,
        };

        if ($llave === null || $llave === '' || $krHash === '') {
            return false;
        }

        // Izipay firma el JSON con las barras "/" sin escapar.
        $normalizado = str_replace('\\/', '/', $krAnswerRaw);
        $calculado = hash_hmac('sha256', $normalizado, $llave);

        return hash_equals($calculado, $krHash);
    }
}
