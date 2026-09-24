<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\EstadoPago;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EstadoPagoTest extends TestCase
{
    /**
     * @return array<string, array{0: ?string, 1: ?string, 2: EstadoPago}>
     */
    public static function respuestasDeIzipay(): array
    {
        return [
            'pagado y capturado' => ['PAID', 'CAPTURED', EstadoPago::Pagado],
            // AUTHORISED sin capturar NO es pagado todavia: el comercio puede
            // anularlo sin pasar por el banco (caso real de produccion, 24/09).
            'autorizado sin capturar' => ['PAID', 'AUTHORISED', EstadoPago::EnVerificacion],
            // Sin CAPTURED explicito no se asume pagado, aunque orderStatus
            // diga PAID: mejor verificar de nuevo que dar dinero por cobrado.
            'paid sin detalle no es pagado' => ['PAID', null, EstadoPago::EnVerificacion],
            'autorizado a validar' => ['PAID', 'AUTHORISED_TO_VALIDATE', EstadoPago::EnVerificacion],
            'esperando autorizacion' => ['RUNNING', 'WAITING_AUTHORISATION', EstadoPago::EnVerificacion],
            'esperando autorizacion a validar' => ['RUNNING', 'WAITING_AUTHORISATION_TO_VALIDATE', EstadoPago::EnVerificacion],
            'bajo verificacion' => ['RUNNING', 'UNDER_VERIFICATION', EstadoPago::EnVerificacion],
            'en curso' => ['RUNNING', null, EstadoPago::EnVerificacion],
            'rechazado' => ['UNPAID', 'REFUSED', EstadoPago::Rechazado],
            'cancelado' => ['UNPAID', 'CANCELLED', EstadoPago::Rechazado],
            'captura fallida' => ['PAID', 'CAPTURE_FAILED', EstadoPago::Rechazado],
            'no pagado sin detalle' => ['UNPAID', null, EstadoPago::Rechazado],
            'expirado' => ['UNPAID', 'EXPIRED', EstadoPago::Expirado],
            'abandonado' => ['ABANDONED', null, EstadoPago::Expirado],
            'desconocido nunca es pagado' => ['LO_QUE_SEA', 'ALGO_NUEVO', EstadoPago::EnVerificacion],
            'todo vacio' => [null, null, EstadoPago::EnVerificacion],
        ];
    }

    #[DataProvider('respuestasDeIzipay')]
    public function test_mapea_la_respuesta_de_izipay(?string $orderStatus, ?string $detailedStatus, EstadoPago $esperado): void
    {
        $this->assertSame($esperado, EstadoPago::desdeRespuestaIzipay($orderStatus, $detailedStatus));
    }

    public function test_el_detalle_manda_sobre_el_order_status(): void
    {
        // CAPTURE_FAILED gana aunque la orden diga PAID: no hay dinero cobrado.
        $this->assertSame(
            EstadoPago::Rechazado,
            EstadoPago::desdeRespuestaIzipay('PAID', 'CAPTURE_FAILED')
        );
    }

    public function test_ignora_mayusculas_y_espacios(): void
    {
        $this->assertSame(EstadoPago::Pagado, EstadoPago::desdeRespuestaIzipay(' paid ', ' captured '));
    }

    public function test_estados_finales(): void
    {
        $this->assertTrue(EstadoPago::Pagado->esFinal());
        $this->assertTrue(EstadoPago::Rechazado->esFinal());
        $this->assertTrue(EstadoPago::Expirado->esFinal());
        $this->assertFalse(EstadoPago::Pendiente->esFinal());
        $this->assertFalse(EstadoPago::EnVerificacion->esFinal());
    }

    public function test_un_estado_final_no_transiciona_a_ninguno(): void
    {
        foreach (EstadoPago::cases() as $destino) {
            $this->assertFalse(EstadoPago::Pagado->puedeTransicionarA($destino));
            $this->assertFalse(EstadoPago::Rechazado->puedeTransicionarA($destino));
            $this->assertFalse(EstadoPago::Expirado->puedeTransicionarA($destino));
        }
    }

    public function test_transiciones_validas_desde_pendiente(): void
    {
        $this->assertTrue(EstadoPago::Pendiente->puedeTransicionarA(EstadoPago::EnVerificacion));
        $this->assertTrue(EstadoPago::Pendiente->puedeTransicionarA(EstadoPago::Pagado));
        $this->assertTrue(EstadoPago::Pendiente->puedeTransicionarA(EstadoPago::Rechazado));
        $this->assertTrue(EstadoPago::Pendiente->puedeTransicionarA(EstadoPago::Expirado));
        $this->assertFalse(EstadoPago::Pendiente->puedeTransicionarA(EstadoPago::Pendiente));
    }

    public function test_en_verificacion_no_retrocede_a_pendiente(): void
    {
        $this->assertFalse(EstadoPago::EnVerificacion->puedeTransicionarA(EstadoPago::Pendiente));
        $this->assertTrue(EstadoPago::EnVerificacion->puedeTransicionarA(EstadoPago::Pagado));
    }
}
