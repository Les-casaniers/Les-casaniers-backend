<?php

namespace App\Mail;

use App\Models\DevisExpress;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DevisExpressConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $devis;

    public function __construct(DevisExpress $devis)
    {
        $this->devis = $devis;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmation de votre demande de devis express - Les Casaniers',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.devis-express-confirmation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}