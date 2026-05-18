<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    public $timestamps = false;

    protected $table = 'newsletter_subscribers';

    protected $fillable = [
        'email',
        'actif',
        'source',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];
}
