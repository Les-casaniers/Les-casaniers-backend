<?php

// namespace App\Models;

// use App\Enums\Sales\FactureStatut;
// use Illuminate\Database\Eloquent\Model;

// class Facture extends Model
// {
//     protected $table = 'factures';

//     protected $fillable = [
//         'commande_id',
//         'facture_ref',
//         'statut',
//         'montant_total',
//         'devise',
//         'methode_paiement',
//         'date_emission',
//         'date_paiement',
//         'pdf_path',
//     ];

//     const CREATED_AT = 'date_creation';
//     const UPDATED_AT = 'date_modification';

//     protected $casts = [
//         'statut' => FactureStatut::class,
//         'montant_total' => 'decimal:2',
//         'date_emission' => 'datetime',
//         'date_paiement' => 'datetime',
//         'date_creation' => 'datetime',
//         'date_modification' => 'datetime',
//     ];

//     public function commande()
//     {
//         return $this->belongsTo(Commande::class, 'commande_id');
//     }
// }



namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Facture extends Model
{
    use HasFactory;

    protected $table = 'factures';

    // Désactiver les timestamps automatiques de Laravel
    public $timestamps = false;

    protected $fillable = [
        'commande_id',
        'facture_ref',
        'statut',
        'montant_total',
        'devise',
        'methode_paiement',
        'date_emission',
        'date_paiement',
        'pdf_path',
        'date_creation',
        'date_modification',
    ];

    protected $casts = [
        'montant_total' => 'decimal:2',
        'date_emission' => 'datetime',
        'date_paiement' => 'datetime',
        'date_creation' => 'datetime',
        'date_modification' => 'datetime',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }
}