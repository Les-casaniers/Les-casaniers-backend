<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Utilisateur;

class NewsletterHebdoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $offres;

    public function __construct(Utilisateur $user, $offres)
    {
        $this->user = $user;
        $this->offres = $offres;
    }

    public function build()
    {
        return $this->from('contact@lescasaniers.mg', 'Les Casaniers')
                    ->subject('Les Casaniers - Nouveautés et offres de la semaine')
                    ->view('emails.newsletter-hebdo');
    }
}