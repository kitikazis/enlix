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

/** Aviso al admin (config('tienda.admin_email')) cuando un pedido pasa a pagado. */
class PedidoPagadoAdmin extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Pedido $pedido) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nuevo pedido pagado: {$this->pedido->codigo}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pedido-pagado-admin',
        );
    }
}
