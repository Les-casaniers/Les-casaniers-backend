<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Utilisateur;  // ✅ Utilisez Utilisateur

class BienvenueMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct(Utilisateur $user)  // ✅ Type hint Utilisateur
    {
        $this->user = $user;
    }

    public function build()
    {
        return $this->from('contact@lescasaniers.mg', 'Les Casaniers')
                    ->subject('Bienvenue chez Les Casaniers !')
                    ->view('emails.bienvenue');
    }
}