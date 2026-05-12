<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvisClient extends Model
{
    protected $table = 'avis_clients';
    
    protected $fillable = [
        'produit_id', 'utilisateur_id', 'note', 'titre', 'corps', 'publie', 'date_creation'
    ];
    
    protected $casts = [
        'note' => 'integer',
        'publie' => 'boolean',
        'date_creation' => 'datetime'
    ];
    
    public $timestamps = false; // On utilise date_creation à la place
    
    // Relation avec le produit
    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
    
    // Relation avec l'utilisateur
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class);
    }
}