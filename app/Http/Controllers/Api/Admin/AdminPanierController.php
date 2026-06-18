<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Models\Panier;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdminPanierController extends Controller
{
    /**
     * Récupérer tous les utilisateurs avec leurs paniers non validés
     */
    public function getUtilisateursAvecPaniers()
    {
        try {
            // Récupérer tous les utilisateurs
            $utilisateurs = Utilisateur::all();

            $result = [];
            foreach ($utilisateurs as $utilisateur) {
                // TEMPORAIRE : Prendre TOUS les paniers, sans filtre de statut
                $paniers = Panier::where('utilisateur_id', $utilisateur->id)->get();

                if ($paniers->count() > 0) {
                    $montantTotal = $paniers->sum(function ($panier) {
                        return (float) $panier->prix_unitaire * (int) $panier->quantite;
                    });

                    $result[] = [
                        'id' => $utilisateur->id,
                        'prenom' => $utilisateur->prenom,
                        'nom' => $utilisateur->nom,
                        'email' => $utilisateur->email,
                        'telephone' => $utilisateur->telephone,
                        'statut' => $utilisateur->statut,
                        'date_creation' => $utilisateur->date_creation,
                        'paniers' => $paniers->map(function ($panier) {
                            $produit = null;
                            if ($panier->produit_id) {
                                // Charger le produit avec ses images
                                $produit = Produit::with('images')->find($panier->produit_id);
                            }

                            return [
                                'id' => $panier->id,
                                'produit_id' => $panier->produit_id,
                                'titre' => $panier->titre ?? ($produit ? $produit->nom : 'Produit'),
                                'prix_unitaire' => (float) $panier->prix_unitaire,
                                'quantite' => (int) $panier->quantite,
                                'statut' => $panier->statut,
                                'date_creation' => $panier->date_creation,
                                'produit' => $produit ? [
                                    'id' => $produit->id,
                                    'nom' => $produit->nom,
                                    'prix' => (float) $produit->prix,
                                    'image_url' => $produit->image_url,
                                    'slug' => $produit->slug,
                                    // AJOUT : Inclure les images
                                    'images' => $produit->images->map(function ($image) {
                                        return [
                                            'id' => $image->id,
                                            'url' => $image->url,
                                            'alt' => $image->alt,
                                            'ordre' => $image->ordre,
                                        ];
                                    }),
                                    // AJOUT : Inclure l'image principale si disponible
                                    'image' => $produit->images->where('ordre', 0)->first()?->url ?? $produit->image_url,
                                ] : null,
                            ];
                        }),
                        'total_paniers' => $paniers->count(),
                        'montant_total' => $montantTotal,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les statistiques des paniers
     */
    public function getStats()
    {
        try {
            // 1. Nombre de clients AVEC panier (utilisateurs qui ont au moins un panier)
            $totalUtilisateursAvecPanier = Panier::distinct('utilisateur_id')->count('utilisateur_id');

            // 2. Nombre total de produits dans tous les paniers (somme des quantités)
            $totalPaniers = Panier::sum('quantite');

            // 3. Chiffre d'affaires potentiel (somme de prix_unitaire * quantite)
            $montantTotalPerdu = Panier::get()->sum(function ($panier) {
                return (float) $panier->prix_unitaire * (int) $panier->quantite;
            });

            // 4. Paniers par jour (les 7 derniers jours)
            $paniersParJour = Panier::select(
                DB::raw('DATE(date_creation) as date'),
                DB::raw('SUM(quantite) as total')
            )
                ->where('date_creation', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(7)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_utilisateurs' => $totalUtilisateursAvecPanier,
                    'total_paniers' => $totalPaniers,
                    'montant_total_perdu' => $montantTotalPerdu,
                    'paniers_par_jour' => $paniersParJour,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur getStats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Envoyer un email de rappel pour le panier
     */
    public function sendEmailRappel(Request $request)
    {
        $request->validate([
            'utilisateur_id' => 'required|exists:utilisateurs,id',
            'email' => 'required|email',
            'sujet' => 'required|string|max:255',
            'contenu_html' => 'required|string',
        ]);

        try {
            $utilisateur = Utilisateur::findOrFail($request->utilisateur_id);

            Mail::send([], [], function ($message) use ($request, $utilisateur) {
                $message->to($request->email, $utilisateur->prenom . ' ' . $utilisateur->nom)
                    ->subject($request->sujet)
                    ->html($request->contenu_html);
            });

            Log::info('Email rappel panier envoyé', [
                'utilisateur_id' => $request->utilisateur_id,
                'email' => $request->email,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email envoyé avec succès à ' . $utilisateur->prenom . ' ' . $utilisateur->nom,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur envoi email rappel panier: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un panier spécifique
     */
    public function deletePanier($id)
    {
        try {
            $panier = Panier::findOrFail($id);
            $panier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Panier supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur suppression panier: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
            ], 500);
        }
    }
}