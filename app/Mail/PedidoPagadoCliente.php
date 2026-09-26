<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Confirmación al cliente cuando su pedido pasa a pagado. Se manda una sola vez, ver PedidoPagoService::aplicarEstado(). */
class PedidoPagadoCliente extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Pedido $pedido) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Confirmación de tu pedido {$this->pedido->codigo} - Enlix",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pedido-pagado-cliente',
        );
    }
}
