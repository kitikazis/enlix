<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Con trustProxies(at: '*') cualquiera podía falsificar X-Forwarded-For y
 * evadir los rate limiters (comprobado en producción: 7/7 intentos de login
 * sin 429 rotando la cabecera). Fuera de 'local' no se confía en ningún proxy.
 */
class ProxiesConfiablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_x_forwarded_for_falsificado_no_cambia_la_ip_vista_por_la_app(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->withHeaders(['X-Forwarded-For' => '10.11.12.13'])
            ->get('/productos');

        $response->assertOk();
        $this->assertSame('203.0.113.7', request()->ip());
    }

    public function test_rotar_x_forwarded_for_no_evade_el_rate_limit_del_login(): void
    {
        $credenciales = [
            'email' => 'objetivo@example.com',
            'password' => 'contraseña-incorrecta',
        ];

        // Los primeros 5 intentos consumen el límite por IP+email.
        for ($i = 1; $i <= 5; $i++) {
            $this->withHeaders(['X-Forwarded-For' => "10.11.12.{$i}"])
                ->post(route('admin.login.store'), $credenciales)
                ->assertRedirect();
        }

        // El 6º, con otra IP falsificada, ya no debe pasar.
        $this->withHeaders(['X-Forwarded-For' => '10.11.12.6'])
            ->post(route('admin.login.store'), $credenciales)
            ->assertStatus(429);
    }

    public function test_limite_por_email_frena_el_ataque_distribuido(): void
    {
        // Cada intento viene de una IP real distinta, así que el límite de
        // IP+email nunca se agota: lo que corta es el límite por email (10/min).
        for ($i = 1; $i <= 10; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "198.51.100.{$i}"])
                ->post(route('admin.login.store'), [
                    'email' => 'victima@example.com',
                    'password' => 'contraseña-incorrecta',
                ])->assertRedirect();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.250'])
            ->post(route('admin.login.store'), [
                'email' => 'victima@example.com',
                'password' => 'contraseña-incorrecta',
            ])->assertStatus(429);
    }
}
