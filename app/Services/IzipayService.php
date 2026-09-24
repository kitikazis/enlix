<?php

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
     * @param  array  $cliente  ['first_name','last_name','email','phone_number','identity_code']
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

        try {
            $response = Http::withBasicAuth(
                (string) config('izipay.username'),
                (string) config('izipay.password')
            )
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post(rtrim((string) config('izipay.base_url'), '/').'/api-payment/V4/Charge/CreatePayment', $payload);
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
