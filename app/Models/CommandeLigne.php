<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommandeLigne extends Model
{
    protected $table = 'commande_lignes';
    
    protected $fillable = [
        'commande_id', 'produit_id', 'quantite', 'prix_unitaire', 'total_ligne'
    ];
    
    protected $casts = [
        'prix_unitaire' => 'decimal:2',
        'total_ligne' => 'decimal:2',
    ];
    
    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }
    
    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
}