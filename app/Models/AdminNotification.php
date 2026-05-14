<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminNotification extends Model
{
    use HasFactory;

    protected $table = 'admin_notifications';

    public $timestamps = false;

    protected $fillable = [
        'type',
        'titre',
        'message',
        'lien',
        'expediteur',
        'meta',
        'lue',
        'date_creation',
        'date_lecture',
    ];

    protected $casts = [
        'lue'           => 'boolean',
        'meta'          => 'array',
        'date_creation' => 'datetime',
        'date_lecture'  => 'datetime',
    ];

    // ─── Scopes ─────────────────────────────────────────

    public function scopeNonLues($query)
    {
        return $query->where('lue', false);
    }

    public function scopeParType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ─── Helpers ────────────────────────────────────────

    public function marquerCommeLue(): self
    {
        $this->update([
            'lue'          => true,
            'date_lecture' => now(),
        ]);

        return $this;
    }
}
