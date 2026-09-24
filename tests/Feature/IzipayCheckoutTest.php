<?php

namespace Tests\Feature;

use App\Models\Pago;
use App\Support\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IzipayCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'izipay.username' => 'test-user',
            'izipay.password' => 'test-password-secreta',
            'izipay.public_key' => 'test-public-key',
            'izipay.sha256_key' => 'test-sha256-key-secreta',
            'izipay.currency' => 'PEN',
        ]);
    }

    private function primerProducto(): array
    {
        $slug = array_key_first(Producto::items());

        return Producto::find($slug);
    }

    private function crearPagoPendiente(array $overrides = []): Pago
    {
        $producto = $this->primerProducto();

        return Pago::create(array_merge([
            'producto' => $producto['slug'],
            'email' => 'cliente@example.com',
            'monto' => $producto['precio_centimos'],
            'moneda' => 'PEN',
            'izipay_order_id' => 'ENX-TEST-'.uniqid(),
            'estado' => 'pendiente',
        ], $overrides));
    }

    private function krAnswer(array $overrides = []): array
    {
        return array_replace_recursive([
            'orderStatus' => 'PAID',
            'orderDetails' => [
                'orderId' => 'ENX-TEST-0001',
                'orderTotalAmount' => 9900,
                'orderCurrency' => 'PEN',
            ],
            'transactions' => [
                [
                    'uuid' => 'uuid-1234',
                    'status' => 'CAPTURED',
                    'detailedStatus' => 'AUTHORISED',
                    'transactionDetails' => [
                        'cardDetails' => [
                            'pan' => '497010XXXXXX0000',
                            'effectiveBrand' => 'VISA',
                        ],
                    ],
                ],
            ],
        ], $overrides);
    }

    /** @return array{'kr-answer': string, 'kr-hash': string} */
    private function firmar(array $answer, string $llave): array
    {
        $raw = json_encode($answer);

        return [
            'kr-answer' => $raw,
            'kr-hash' => hash_hmac('sha256', $raw, $llave),
        ];
    }

    private function fakeFormToken(string $formToken = 'tok_fake_123'): void
    {
        Http::fake([
            'api.micuentaweb.pe/*' => Http::response([
                'status' => 'SUCCESS',
                'answer' => ['formToken' => $formToken, 'publicKey' => 'pk_test'],
            ], 200),
        ]);
    }

    public function test_form_token_usa_siempre_el_precio_del_servidor(): void
    {
        $this->fakeFormToken();
        $producto = $this->primerProducto();

        $response = $this->postJson(route('izipay.form-token'), [
            'producto' => $producto['slug'],
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'email' => 'juan@example.com',
            'phone_number' => '+51999999999',
            // Un atacante intenta colar un monto/precio propio: debe ser ignorado.
            'monto' => 1,
            'precio_centimos' => 1,
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => $request['amount'] === $producto['precio_centimos']
            && $request['currency'] === 'PEN');

        $this->assertDatabaseHas('pagos', [
            'producto' => $producto['slug'],
            'monto' => $producto['precio_centimos'],
            'estado' => 'pendiente',
        ]);
    }

    public function test_form_token_con_status_error_en_body_http_200_no_se_trata_como_exito(): void
    {
        // Izipay responde HTTP 200 incluso con credenciales invalidas: el
        // error va dentro del body ("status":"ERROR"), no en el codigo HTTP.
        Http::fake([
            'api.micuentaweb.pe/*' => Http::response([
                'status' => 'ERROR',
                'answer' => ['errorCode' => 'INT_905', 'errorMessage' => 'invalid login or private key'],
            ], 200),
        ]);
        $producto = $this->primerProducto();

        $response = $this->postJson(route('izipay.form-token'), [
            'producto' => $producto['slug'],
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'email' => 'juan@example.com',
            'phone_number' => '+51999999999',
        ]);

        $response->assertStatus(422)->assertJson(['ok' => false]);
        $this->assertDatabaseMissing('pagos', ['producto' => $producto['slug']]);
    }

    public function test_moneda_distinta_no_marca_pagado(): void
    {
        $pago = $this->crearPagoPendiente();

        $answer = $this->krAnswer([
            'orderDetails' => [
                'orderId' => $pago->izipay_order_id,
                'orderTotalAmount' => $pago->monto,
                'orderCurrency' => 'USD',
            ],
        ]);
        $firma = $this->firmar($answer, config('izipay.password'));

        $this->postJson(route('izipay.ipn'), array_merge($firma, [
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]))->assertOk();

        $this->assertSame('pendiente', $pago->fresh()->estado);
    }

    public function test_monto_distinto_no_marca_pagado(): void
    {
        $pago = $this->crearPagoPendiente();

        $answer = $this->krAnswer([
            'orderDetails' => [
                'orderId' => $pago->izipay_order_id,
                'orderTotalAmount' => $pago->monto + 1,
                'orderCurrency' => $pago->moneda,
            ],
        ]);
        $firma = $this->firmar($answer, config('izipay.password'));

        $this->postJson(route('izipay.ipn'), array_merge($firma, [
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]))->assertOk();

        $this->assertSame('pendiente', $pago->fresh()->estado);
    }

    public function test_kr_hash_key_incorrecto_es_rechazado(): void
    {
        $pago = $this->crearPagoPendiente();

        $answer = $this->krAnswer([
            'orderDetails' => [
                'orderId' => $pago->izipay_order_id,
                'orderTotalAmount' => $pago->monto,
                'orderCurrency' => $pago->moneda,
            ],
        ]);
        // Firmado con la llave correcta pero declarando el kr-hash-key equivocado
        // (o firmado con la llave incorrecta para el canal usado).
        $firma = $this->firmar($answer, config('izipay.sha256_key'));

        $this->postJson(route('izipay.ipn'), array_merge($firma, [
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password', // IPN exige 'password', no 'sha256_hmac'
        ]))->assertOk();

        $this->assertSame('pendiente', $pago->fresh()->estado);
    }

    public function test_pago_pagado_no_regresa_a_rechazado_tras_unpaid_posterior(): void
    {
        $pago = $this->crearPagoPendiente(['estado' => 'pagado']);

        $answer = $this->krAnswer([
            'orderStatus' => 'UNPAID',
            'orderDetails' => [
                'orderId' => $pago->izipay_order_id,
                'orderTotalAmount' => $pago->monto,
                'orderCurrency' => $pago->moneda,
            ],
        ]);
        $firma = $this->firmar($answer, config('izipay.password'));

        $this->postJson(route('izipay.ipn'), array_merge($firma, [
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]))->assertOk();

        $this->assertSame('pagado', $pago->fresh()->estado);
    }

    public function test_ipn_repetido_cinco_veces_deja_un_solo_registro_y_estado_estable(): void
    {
        $pago = $this->crearPagoPendiente();

        $answer = $this->krAnswer([
            'orderDetails' => [
                'orderId' => $pago->izipay_order_id,
                'orderTotalAmount' => $pago->monto,
                'orderCurrency' => $pago->moneda,
            ],
        ]);
        $firma = $this->firmar($answer, config('izipay.password'));
        $payload = array_merge($firma, [
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('izipay.ipn'), $payload)->assertOk();
        }

        $this->assertSame(1, Pago::where('izipay_order_id', $pago->izipay_order_id)->count());
        $this->assertSame('pagado', $pago->fresh()->estado);
    }

    public function test_ipn_con_order_id_inexistente_responde_200_sin_crear_registro(): void
    {
        $totalAntes = Pago::count();

        $answer = $this->krAnswer([
            'orderDetails' => [
                'orderId' => 'ENX-NO-EXISTE',
                'orderTotalAmount' => 9900,
                'orderCurrency' => 'PEN',
            ],
        ]);
        $firma = $this->firmar($answer, config('izipay.password'));

        $this->postJson(route('izipay.ipn'), array_merge($firma, [
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'password',
        ]))->assertOk();

        $this->assertSame($totalAntes, Pago::count());
    }

    public function test_validar_con_order_id_inexistente_no_crea_registro(): void
    {
        $totalAntes = Pago::count();

        $answer = $this->krAnswer([
            'orderDetails' => [
                'orderId' => 'ENX-NO-EXISTE-BROWSER',
                'orderTotalAmount' => 9900,
                'orderCurrency' => 'PEN',
            ],
        ]);
        $firma = $this->firmar($answer, config('izipay.sha256_key'));

        $this->postJson(route('izipay.validar'), array_merge($firma, [
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'sha256_hmac',
        ]));

        $this->assertSame($totalAntes, Pago::count());
    }

    public function test_logs_no_contienen_form_token_ni_kr_hash(): void
    {
        $formTokenSecreto = 'tok_super_secreto_no_debe_verse_en_logs';
        $this->fakeFormToken($formTokenSecreto);

        $logPath = storage_path('logs/test-izipay-security.log');
        @unlink($logPath);
        config(['logging.channels.test_izipay' => ['driver' => 'single', 'path' => $logPath, 'level' => 'debug']]);
        config(['logging.default' => 'test_izipay']);

        $producto = $this->primerProducto();

        // Camino feliz: se genera un formToken (secreto) que jamas debe quedar en el log.
        $this->postJson(route('izipay.form-token'), [
            'producto' => $producto['slug'],
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'email' => 'juan@example.com',
            'phone_number' => '+51999999999',
        ])->assertOk();

        // Camino de error: firma invalida (ejercita el Log::warning con kr-hash disponible).
        $firmaInvalida = 'firma-claramente-invalida-0000';
        $this->postJson(route('izipay.validar'), [
            'kr-answer' => json_encode($this->krAnswer()),
            'kr-hash' => $firmaInvalida,
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => 'sha256_hmac',
        ]);

        $this->assertFileExists($logPath);
        $contenido = file_get_contents($logPath);

        $this->assertStringNotContainsString($formTokenSecreto, $contenido);
        $this->assertStringNotContainsString($firmaInvalida, $contenido);
        $this->assertStringNotContainsString((string) config('izipay.password'), $contenido);
        $this->assertStringNotContainsString((string) config('izipay.sha256_key'), $contenido);

        @unlink($logPath);
    }

    public function test_headers_de_seguridad_presentes_en_productos(): void
    {
        $response = $this->get('/productos');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotNull($response->headers->get('Permissions-Policy'));

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString('*.micuentaweb.pe', $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);

        preg_match("/'nonce-([a-zA-Z0-9]+)'/", $csp, $m);
        $this->assertNotEmpty($m, 'El CSP debe declarar un nonce para script-src.');
        $response->assertSee('nonce="'.$m[1].'"', false);
    }

    public function test_comando_expira_pagos_pendientes_de_mas_de_24_horas(): void
    {
        $viejo = $this->crearPagoPendiente();
        $viejo->forceFill(['created_at' => now()->subDay()->subMinute()])->save();

        $reciente = $this->crearPagoPendiente();

        $this->artisan('izipay:expirar-pendientes')->assertSuccessful();

        $this->assertSame('expirado', $viejo->fresh()->estado);
        $this->assertSame('pendiente', $reciente->fresh()->estado);
    }

    public function test_rate_limit_form_token_por_ip_y_email(): void
    {
        $this->fakeFormToken();
        $producto = $this->primerProducto();

        $payload = [
            'producto' => $producto['slug'],
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'email' => 'mismo@example.com',
            'phone_number' => '+51999999999',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('izipay.form-token'), $payload)->assertOk();
        }

        $this->postJson(route('izipay.form-token'), $payload)->assertStatus(429);
    }
}
