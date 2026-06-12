<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklyNewsletterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $recipient;
    public $content;

    public function __construct($recipient, $content)
    {
        $this->recipient = $recipient;
        $this->content = $content;
    }

    public function build()
    {
        return $this->from('newsletter@lescasaniers.mg', 'Les Casaniers')
                    ->subject($this->content['subject'])
                    ->view('emails.weekly-newsletter');
    }
}