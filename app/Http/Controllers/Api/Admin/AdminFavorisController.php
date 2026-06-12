<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Models\Favori;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AdminFavorisController extends Controller
{
    /**
     * Récupérer tous les utilisateurs avec leurs favoris
     */
    public function getUtilisateursAvecFavoris()
    {
        $utilisateurs = Utilisateur::all();
        
        $result = [];
        foreach ($utilisateurs as $utilisateur) {
            $favoris = Favori::where('utilisateur_id', $utilisateur->id)
                ->with('produit')
                ->get();
            
            $result[] = [
                'id' => $utilisateur->id,
                'prenom' => $utilisateur->prenom,
                'nom' => $utilisateur->nom,
                'email' => $utilisateur->email,
                'telephone' => $utilisateur->telephone,
                'statut' => $utilisateur->statut,
                'date_creation' => $utilisateur->date_creation,
                'favoris' => $favoris->map(function ($favori) {
                    return [
                        'id' => $favori->id,
                        'produit_id' => $favori->produit_id,
                        'date_creation' => $favori->date_creation,
                        'produit' => $favori->produit ? [
                            'id' => $favori->produit->id,
                            'nom' => $favori->produit->nom,
                            'prix' => $favori->produit->prix,
                            'image_url' => $favori->produit->image_url,
                            'slug' => $favori->produit->slug,
                        ] : null,
                    ];
                }),
                'total_favoris' => $favoris->count(),
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
    
    /**
     * Récupérer tous les favoris avec les détails des produits
     */
    public function getAllFavoris()
    {
        $favoris = Favori::with(['utilisateur', 'produit'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $favoris,
        ]);
    }
    
    /**
     * Récupérer les favoris d'un utilisateur spécifique
     */
    public function getFavorisByUser($userId)
    {
        $utilisateur = Utilisateur::findOrFail($userId);
        
        $favoris = Favori::where('utilisateur_id', $userId)
            ->with('produit')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'utilisateur' => [
                    'id' => $utilisateur->id,
                    'prenom' => $utilisateur->prenom,
                    'nom' => $utilisateur->nom,
                    'email' => $utilisateur->email,
                ],
                'favoris' => $favoris,
                'total' => $favoris->count(),
            ],
        ]);
    }
    
    /**
     * Envoyer un email de rappel pour les favoris
     */
    public function sendEmailFavoris(Request $request)
    {
        $request->validate([
            'utilisateur_id' => 'required|exists:utilisateurs,id',
            'email' => 'required|email',
            'sujet' => 'required|string|max:255',
            'contenu_html' => 'required|string',
        ]);
        
        try {
            $utilisateur = Utilisateur::findOrFail($request->utilisateur_id);
            
            // Envoyer l'email
            Mail::send([], [], function ($message) use ($request, $utilisateur) {
                $message->to($request->email, $utilisateur->prenom . ' ' . $utilisateur->nom)
                        ->subject($request->sujet)
                        ->html($request->contenu_html);
            });
            
            // Log de l'action
            Log::info('Email favoris envoyé', [
                'utilisateur_id' => $request->utilisateur_id,
                'email' => $request->email,
                'admin_id' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Email envoyé avec succès à ' . $utilisateur->prenom . ' ' . $utilisateur->nom,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur envoi email favoris: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Obtenir les statistiques des favoris
     */
    public function getStats()
    {
        $totalUtilisateurs = Utilisateur::count();
        $utilisateursAvecFavoris = Favori::distinct('utilisateur_id')->count('utilisateur_id');
        $totalFavoris = Favori::count();
        
        // Top 5 des produits les plus favorisés
        $topProduits = Favori::select('produit_id')
            ->with('produit')
            ->groupBy('produit_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'produit_id' => $item->produit_id,
                    'compteur' => Favori::where('produit_id', $item->produit_id)->count(),
                    'produit' => $item->produit ? [
                        'nom' => $item->produit->nom,
                        'prix' => $item->produit->prix,
                    ] : null,
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_utilisateurs' => $totalUtilisateurs,
                'utilisateurs_avec_favoris' => $utilisateursAvecFavoris,
                'total_favoris' => $totalFavoris,
                'moyenne_favoris_par_utilisateur' => $utilisateursAvecFavoris > 0 
                    ? round($totalFavoris / $utilisateursAvecFavoris, 2) 
                    : 0,
                'top_produits' => $topProduits,
            ],
        ]);
    }
}