<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\MetodoPago;
use Tests\TestCase;

class MetodoPagoTest extends TestCase
{
    public function test_detecta_tarjeta_por_la_presencia_de_card_details(): void
    {
        $answer = [
            'transactions' => [[
                'transactionDetails' => ['cardDetails' => ['pan' => '497010XXXXXX0000', 'effectiveBrand' => 'VISA']],
            ]],
        ];

        $this->assertSame(MetodoPago::Tarjeta, MetodoPago::desdeRespuestaIzipay($answer));
    }

    public function test_detecta_yape_por_payment_method_type(): void
    {
        $answer = ['transactions' => [['paymentMethodType' => 'YAPE_CODE']]];

        $this->assertSame(MetodoPago::Yape, MetodoPago::desdeRespuestaIzipay($answer));
    }

    public function test_detecta_plin_por_payment_method_type(): void
    {
        $answer = ['transactions' => [['paymentMethodType' => 'PLIN_INTERBANK']]];

        $this->assertSame(MetodoPago::Plin, MetodoPago::desdeRespuestaIzipay($answer));
    }

    public function test_detecta_qr_por_payment_method_type(): void
    {
        $answer = ['transactions' => [['paymentMethodType' => 'QR_CODE']]];

        $this->assertSame(MetodoPago::Qr, MetodoPago::desdeRespuestaIzipay($answer));
    }

    public function test_cae_en_otro_si_no_reconoce_nada(): void
    {
        $this->assertSame(MetodoPago::Otro, MetodoPago::desdeRespuestaIzipay([]));
        $this->assertSame(
            MetodoPago::Otro,
            MetodoPago::desdeRespuestaIzipay(['transactions' => [['paymentMethodType' => 'ALGO_DESCONOCIDO']]])
        );
    }
}
