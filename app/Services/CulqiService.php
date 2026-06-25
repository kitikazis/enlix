<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Cliente del API de Culqi.
 *
 * Habla con Culqi usando la SECRET KEY (solo backend). La tarjeta nunca pasa
 * por aquí: el navegador la tokeniza con Culqi.js.
 *
 * - crearCargo()  -> tarjeta / Yape (pago con token, inmediato).
 * - crearOrden()  -> billeteras / banca móvil / Cuotéalo (pago con orden).
 * - obtenerOrden()-> consulta el estado de una orden para confirmar el pago.
 */
class CulqiService
{
    /** Cliente HTTP base ya autenticado contra Culqi. */
    private function cliente(): PendingRequest
    {
        return Http::withToken(config('culqi.secret_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->baseUrl(rtrim((string) config('culqi.base_url'), '/'));
    }

    /** Normaliza la respuesta de Culqi a un arreglo uniforme. */
    private function resultado(Response $response): array
    {
        return [
            'ok'   => $response->successful(),
            'http' => $response->status(),
            'data' => $response->json(),
        ];
    }

    private function errorConexion(): array
    {
        return [
            'ok'   => false,
            'http' => 0,
            'data' => ['user_message' => 'No se pudo conectar con la pasarela de pago.'],
        ];
    }

    /**
     * Crea un cargo (tarjeta / Yape) a partir de un token.
     *
     * @return array{ok: bool, http: int, data: array|null}
     */
    public function crearCargo(string $sourceId, int $montoCentimos, string $email, array $metadata = []): array
    {
        $payload = [
            'amount'        => $montoCentimos,
            'currency_code' => config('culqi.currency', 'PEN'),
            'email'         => $email,
            'source_id'     => $sourceId,
        ];

        if (! empty($metadata)) {
            $payload['metadata'] = $metadata;
        }

        try {
            return $this->resultado($this->cliente()->post('/charges', $payload));
        } catch (ConnectionException $e) {
            return $this->errorConexion();
        }
    }

    /**
     * Crea una orden de pago (billeteras / banca móvil / Cuotéalo).
     *
     * @param  array  $cliente  ['first_name','last_name','email','phone_number']
     * @return array{ok: bool, http: int, data: array|null}
     */
    public function crearOrden(int $montoCentimos, string $descripcion, string $orderNumber, array $cliente): array
    {
        $payload = [
            'amount'          => $montoCentimos,
            'currency_code'   => config('culqi.currency', 'PEN'),
            'description'     => $descripcion,
            'order_number'    => $orderNumber,
            'expiration_date' => now()->addDay()->getTimestamp(),
            'confirm'         => false,
            'client_details'  => [
                'first_name'   => $cliente['first_name'],
                'last_name'    => $cliente['last_name'],
                'email'        => $cliente['email'],
                'phone_number' => $cliente['phone_number'],
            ],
        ];

        try {
            return $this->resultado($this->cliente()->post('/orders', $payload));
        } catch (ConnectionException $e) {
            return $this->errorConexion();
        }
    }

    /**
     * Consulta una orden para verificar su estado (state === 'paid').
     *
     * @return array{ok: bool, http: int, data: array|null}
     */
    public function obtenerOrden(string $orderId): array
    {
        try {
            return $this->resultado($this->cliente()->get('/orders/'.$orderId));
        } catch (ConnectionException $e) {
            return $this->errorConexion();
        }
    }
}
