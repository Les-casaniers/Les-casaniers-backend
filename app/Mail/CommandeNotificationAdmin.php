<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommandeNotificationAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public $commande;
    public $utilisateur;
    public $produits;

    public function __construct($commande, $utilisateur)
    {
        $this->commande = $commande;
        $this->utilisateur = $utilisateur;
        $this->produits = Commande::where('commande_uuid', $commande->commande_uuid)
            ->orderBy('id')
            ->get();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle commande - Les Casaniers',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.commande-notification-admin',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
