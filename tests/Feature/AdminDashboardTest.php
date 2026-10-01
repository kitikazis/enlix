<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EstadoPago;
use App\Models\Pago;
use App\Models\User;
use App\Support\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_es_redirigido_a_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect('/admin/login');
    }

    public function test_csp_permite_unsafe_eval_porque_la_pagina_usa_alpine(): void
    {
        // Sin esto, Alpine.js no puede evaluar x-model/x-show/@click (todas
        // sus expresiones usan new Function()) y se rompe en silencio: los
        // modales quedan siempre abiertos y los inputs no reciben texto.
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString('unsafe-eval', $csp);
    }

    public function test_csp_del_login_admin_no_tiene_unsafe_eval(): void
    {
        // admin.login no carga Alpine (es un formulario plano): no necesita
        // el CSP mas permisivo.
        $csp = $this->get(route('admin.login'))->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
    }

    public function test_el_script_inline_del_layout_lleva_el_nonce_del_csp(): void
    {
        // Sin nonce, el CSP bloquea el <script> inline entero (no solo
        // eval()): scrollFade() quedaria sin definir y cualquier tarjeta
        // con scroll (x-data="scrollFade()") rompe el scope de Alpine para
        // todo lo que tenga adentro (ver admin-dashboard.blade.php).
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $csp = $response->headers->get('Content-Security-Policy');
        preg_match("/'nonce-([a-zA-Z0-9]+)'/", $csp, $m);
        $this->assertNotEmpty($m, 'El CSP debe declarar un nonce para script-src.');
        $response->assertSee('<script nonce="'.$m[1].'">', false);
    }

    private function crearPago(EstadoPago $estado, int $monto, string $producto = 'plan-web-basico'): Pago
    {
        return Pago::create([
            'producto' => $producto,
            // No @example.com: el dashboard excluye datos de prueba por
            // defecto (Pago::esPrueba()) y estos pagos deben contar.
            'email' => 'cliente@gmail.com',
            'monto' => $monto,
            'moneda' => 'PEN',
            'izipay_order_id' => 'ENX-DASH-'.uniqid(),
            'estado' => $estado,
        ]);
    }

    public function test_suma_los_ingresos_solo_de_pagos_realmente_pagados(): void
    {
        $user = User::factory()->create();
        $this->crearPago(EstadoPago::Pagado, 9900);
        $this->crearPago(EstadoPago::Pagado, 19900);
        $this->crearPago(EstadoPago::Rechazado, 34900);
        $this->crearPago(EstadoPago::Pendiente, 9900);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('S/ 298.00'); // 99 + 199, no cuenta el rechazado ni el pendiente
    }

    public function test_calcula_la_tasa_de_conversion(): void
    {
        $user = User::factory()->create();
        $this->crearPago(EstadoPago::Pagado, 9900);
        $this->crearPago(EstadoPago::Rechazado, 9900);
        $this->crearPago(EstadoPago::Rechazado, 9900);
        $this->crearPago(EstadoPago::Rechazado, 9900);

        // 1 de 4 pagado = 25%
        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertSee('25%');
    }

    public function test_muestra_ventas_por_producto(): void
    {
        $user = User::factory()->create();
        $this->crearPago(EstadoPago::Pagado, 9900, 'plan-web-basico');
        $this->crearPago(EstadoPago::Pagado, 9900, 'plan-web-basico');
        $this->crearPago(EstadoPago::Pagado, 34900, 'plan-web-empresarial');

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        // El nombre real del catalogo (con tilde), no el slug crudo.
        $response->assertSee('Plan Web Básico');
        $response->assertSee('2 ventas');
        $response->assertSee('Plan Web Empresarial');
        $response->assertSee('1 ventas');
    }

    public function test_sin_pagos_no_revienta_y_muestra_ceros(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('S/ 0.00');
    }

    public function test_muestra_el_conteo_de_productos_activos(): void
    {
        $user = User::factory()->create();
        $totalProductos = \App\Models\Producto::count();
        $producto = \App\Models\Producto::where('slug', 'plan-web-basico')->first();
        $this->actingAs($user)->patch(route('admin.productos.alternar-activo', $producto));

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertSee(($totalProductos - 1).' de '.$totalProductos.' activos');
    }

    public function test_filtro_por_estado_solo_muestra_esos_pagos(): void
    {
        $user = User::factory()->create();
        $this->crearPago(EstadoPago::Pagado, 9900)->update(['email' => 'pagado@gmail.com']);
        $this->crearPago(EstadoPago::Rechazado, 9900)->update(['email' => 'rechazado@gmail.com']);

        $response = $this->actingAs($user)
            ->get(route('admin.dashboard', ['estado' => EstadoPago::Pagado->value]));

        $response->assertOk();
        $response->assertSee('pagado@gmail.com');
        $response->assertDontSee('rechazado@gmail.com');
    }

    public function test_calcula_la_tasa_de_rechazo(): void
    {
        $user = User::factory()->create();
        $this->crearPago(EstadoPago::Pagado, 9900);
        $this->crearPago(EstadoPago::Rechazado, 9900);
        $this->crearPago(EstadoPago::Rechazado, 9900);
        $this->crearPago(EstadoPago::Anulado, 9900);

        // 2 de 4 rechazados = 50%. Anulado no cuenta como rechazo.
        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertSee('50%');
    }

    public function test_un_pago_fuera_del_rango_de_30_dias_no_cuenta_en_los_kpis(): void
    {
        // El listado completo (/admin/pagos) no depende del rango del
        // dashboard; lo que no debe pasar es que ingresosRango (el KPI de
        // arriba) cuente un pago fuera de los ultimos 30 dias.
        $user = User::factory()->create();
        $viejo = $this->crearPago(EstadoPago::Pagado, 50000);
        $viejo->forceFill(['created_at' => now()->subDays(40)])->save();

        $response = $this->actingAs($user)->get(route('admin.dashboard', ['rango' => '30d']));

        $response->assertOk();
        $response->assertSee('Ingresos · 30 días', false);
        $response->assertSee('S/ 0.00');
    }

    public function test_rango_mes_si_incluye_un_pago_de_hace_40_dias_si_el_mes_es_largo(): void
    {
        // El rango 'mes' es el mes calendario actual, no una ventana movil:
        // solo verificamos que aceptar el parametro no rompe la pagina.
        $user = User::factory()->create();
        $this->crearPago(EstadoPago::Pagado, 9900);

        $this->actingAs($user)
            ->get(route('admin.dashboard', ['rango' => 'mes']))
            ->assertOk();
    }

    public function test_un_rango_invalido_cae_a_30_dias_sin_error(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard', ['rango' => 'lo-que-sea']))
            ->assertOk();
    }

    public function test_emails_aparecen_enmascarados_por_defecto(): void
    {
        $user = User::factory()->create();
        $this->crearPago(EstadoPago::Pagado, 9900)->update(['email' => 'cliente@gmail.com']);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertSee('cl•••@gmail.com');
    }

    public function test_exportar_csv_incluye_los_pagos_del_rango(): void
    {
        $user = User::factory()->create();
        $this->crearPago(EstadoPago::Pagado, 9900)->update(['email' => 'export@gmail.com']);

        $response = $this->actingAs($user)->get(route('admin.dashboard.exportar', ['rango' => '30d']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $contenido = $response->streamedContent();
        $this->assertStringContainsString('export@gmail.com', $contenido);
        $this->assertStringContainsString('Plan Web Básico', $contenido);
    }

    public function test_exportar_csv_exige_login(): void
    {
        $this->get(route('admin.dashboard.exportar'))->assertRedirect('/admin/login');
    }
}
