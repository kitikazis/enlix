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

/** Copia automatica al consumidor cuando registra un reclamo/queja (ver ReclamoController::store()). */
class ReclamoRegistrado extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reclamo $reclamo) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Copia de tu {$this->reclamo->tipo->etiqueta()} N.° {$this->reclamo->numero_correlativo} - Enlix",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reclamo-registrado',
        );
    }
}
