<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AvisClient;
use App\Models\Commande;
use App\Models\Favori;
use App\Models\Facture;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Retourne les données nécessaires à l'aperçu du tableau de bord client.
     */
    public function apercu(Request $request)
    {
        $userId = (int) $request->user()->id;

        // Une commande contient une ligne par article : on les regroupe par numéro.
        $commandes = Commande::where('utilisateur_id', $userId)
            ->orderByDesc('date_creation')
            ->get()
            ->unique('commande_uuid')
            ->values();

        $enCours = $commandes->filter(
            fn (Commande $commande) => in_array((string) $commande->statut->value, ['en_attente', 'payee', 'en_traitement'], true)
        )->count();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'commandes' => $commandes->count(),
                    'en_cours' => $enCours,
                    'favoris' => Favori::where('utilisateur_id', $userId)->count(),
                    'avis' => AvisClient::where('utilisateur_id', $userId)->count(),
                ],
                'recent_orders' => $commandes->take(3)->values(),
            ],
        ]);
    }

    /**
     * Historique condensé des commandes du client pour son espace personnel.
     */
    public function commandes(Request $request)
    {
        $userId = (int) $request->user()->id;
        $groupes = Commande::where('utilisateur_id', $userId)
            ->orderByDesc('date_creation')
            ->get()
            ->groupBy('commande_uuid')
            ->map(function ($items) {
                $commande = $items->first();

                return [
                    'id' => $commande->id,
                    'commande_uuid' => $commande->commande_uuid,
                    'statut' => $commande->statut->value,
                    'total' => (float) $commande->total,
                    'devise' => $commande->devise,
                    'date_creation' => $commande->date_creation,
                    'quantite' => $items->sum('quantite'),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total' => $groupes->count(),
                    'en_cours' => $groupes->whereIn('statut', ['en_attente', 'payee', 'en_traitement', 'expediee'])->count(),
                    'livrees' => $groupes->where('statut', 'terminee')->count(),
                    'annulees' => $groupes->whereIn('statut', ['annulee', 'remboursee'])->count(),
                ],
                'commandes' => $groupes,
            ],
        ]);
    }

    /** Liste et compteurs des factures appartenant au client connecté. */
    public function factures(Request $request)
    {
        $factures = Facture::whereHas('commande', fn ($query) => $query->where('utilisateur_id', $request->user()->id))
            ->with('commande:id,commande_uuid,utilisateur_id')
            ->orderByDesc('date_creation')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total' => $factures->count(),
                    'payees' => $factures->where('statut', 'payee')->count(),
                    'en_cours' => $factures->whereIn('statut', ['brouillon', 'emise', 'en_attente'])->count(),
                ],
                'factures' => $factures,
            ],
        ]);
    }
}
