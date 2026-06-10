<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterAbonnement extends Model
{
    protected $table = 'newsletter_abonnements';

    protected $fillable = [
        'email',
        'prenom',
        'nom',
        'actif'
    ];

    protected $casts = [
        'actif' => 'boolean'
    ];
}