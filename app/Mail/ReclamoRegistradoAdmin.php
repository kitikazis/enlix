<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Reclamo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso al admin (config('tienda.admin_email')) cuando se registra un reclamo/queja nuevo. */
class ReclamoRegistradoAdmin extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reclamo $reclamo) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nuevo {$this->reclamo->tipo->etiqueta()} N.° {$this->reclamo->numero_correlativo}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reclamo-registrado-admin',
        );
    }
}
