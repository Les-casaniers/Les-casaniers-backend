<?php
//app/Http/Controllers/Api/Livreur/LivreurController.php

namespace App\Http\Controllers\Api\Livreur;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LivreurController extends Controller
{
    /**
     * Récupérer les commandes pour le livreur
     */
    // public function getCommandes(Request $request): JsonResponse
    // {
    //     try {
    //         // ✅ Récupérer les commandes avec les relations utilisateur et adresses
    //         $query = Commande::with([
    //             'utilisateur',
    //             'adresseExpédition', // Relation avec l'adresse de livraison
    //             'adresseFacturation' // Relation avec l'adresse de facturation
    //         ])->orderBy('date_creation', 'desc');

    //         // Filtrer par statut si demandé
    //         if ($request->has('statut') && $request->statut !== 'all') {
    //             $query->where('statut', $request->statut);
    //         }

    //         // Filtrer par recherche si demandé
    //         if ($request->has('search') && !empty($request->search)) {
    //             $search = $request->search;
    //             $query->where(function($q) use ($search) {
    //                 $q->where('commande_uuid', 'like', "%{$search}%")
    //                   ->orWhereHas('utilisateur', function($u) use ($search) {
    //                       $u->where('nom', 'like', "%{$search}%")
    //                         ->orWhere('prenom', 'like', "%{$search}%")
    //                         ->orWhere('email', 'like', "%{$search}%");
    //                   });
    //             });
    //         }

    //         // Récupérer les commandes avec pagination
    //         $perPage = $request->per_page ?? 50;
    //         $commandes = $query->paginate($perPage);

    //         // Transformation des données pour le frontend
    //         $commandesData = $commandes->map(function($commande) {
    //             // Extraire les informations de meta_json
    //             $metaData = [];
    //             if ($commande->meta_json) {
    //                 try {
    //                     $metaData = is_string($commande->meta_json) 
    //                         ? json_decode($commande->meta_json, true) 
    //                         : $commande->meta_json;
    //                 } catch (\Exception $e) {
    //                     $metaData = [];
    //                 }
    //             }

    //             // Construire les produits
    //             $produits = [];
    //             if ($commande->produits && count($commande->produits) > 0) {
    //                 $produits = $commande->produits;
    //             } elseif (isset($metaData['produits']) && is_array($metaData['produits'])) {
    //                 $produits = $metaData['produits'];
    //             } elseif (isset($metaData['items']) && is_array($metaData['items'])) {
    //                 $produits = $metaData['items'];
    //             } else {
    //                 // Produit unique
    //                 $produits = [[
    //                     'id' => $commande->produit_id ?? null,
    //                     'nom' => $commande->titre ?? 'Produit sans nom',
    //                     'quantite' => $commande->quantite ?? 1,
    //                     'prix_unitaire' => $commande->prix_unitaire ?? 0,
    //                     'sous_total' => ($commande->prix_unitaire ?? 0) * ($commande->quantite ?? 1)
    //                 ]];
    //             }

    //             // Récupérer les photos des produits
    //             $photos = [];
    //             foreach ($produits as $produit) {
    //                 if (isset($produit['image_url']) && $produit['image_url']) {
    //                     $photos[] = $produit['image_url'];
    //                 }
    //             }

    //             // ✅ RÉCUPÉRER L'ADRESSE DE LIVRAISON DEPUIS LA RELATION
    //             $adresseLivraison = 'Adresse non disponible';

    //             // Essayer d'abord avec la relation adresseExpédition
    //             if ($commande->adresseExpédition) {
    //                 $adresse = $commande->adresseExpédition;
    //                 $adresseLivraison = $adresse->getFullAddress();
    //             } 
    //             // Sinon, essayer de récupérer depuis meta_json
    //             elseif (isset($metaData['adresse_livraison'])) {
    //                 $adresseLivraison = $metaData['adresse_livraison'];
    //             } elseif (isset($metaData['adresse'])) {
    //                 $adresseLivraison = $metaData['adresse'];
    //             } elseif (isset($metaData['adresse_expedition'])) {
    //                 $adresseLivraison = $metaData['adresse_expedition'];
    //             }

    //             return [
    //                 'id' => $commande->id,
    //                 'commande_uuid' => $commande->commande_uuid,
    //                 'statut' => $commande->statut,
    //                 'total' => $commande->total,
    //                 'sous_total' => $commande->sous_total,
    //                 'livraison' => $commande->livraison,
    //                 'devise' => $commande->devise ?? 'MGA',
    //                 'date_creation' => $commande->date_creation,
    //                 'utilisateur' => $commande->utilisateur ? [
    //                     'id' => $commande->utilisateur->id,
    //                     'nom' => $commande->utilisateur->nom,
    //                     'prenom' => $commande->utilisateur->prenom,
    //                     'email' => $commande->utilisateur->email,
    //                     'telephone' => $commande->utilisateur->telephone ?? 'Téléphone non disponible'
    //                 ] : null,
    //                 'produits' => $produits,
    //                 'photos' => $photos,
    //                 'adresse_livraison' => $adresseLivraison,
    //                 'adresse_expedition_id' => $commande->adresse_expedition_id,
    //                 'meta' => $metaData,
    //             ];
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'data' => $commandesData,
    //             'pagination' => [
    //                 'current_page' => $commandes->currentPage(),
    //                 'total' => $commandes->total(),
    //                 'per_page' => $commandes->perPage(),
    //                 'last_page' => $commandes->lastPage(),
    //             ]
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Erreur lors du chargement des commandes: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    // NOTE: Frontend JavaScript/TypeScript function "fetchCommandes" removed because it was accidentally
    // injected into the PHP class file and caused syntax errors (unexpected token =>, {, etc.).
    // Keep frontend code in the JS/TS project; this controller should contain only PHP methods.

    public function getCommandes(Request $request): JsonResponse
    {
        try {
            // ✅ Récupérer les commandes avec les relations utilisateur et adresses
            $query = Commande::with([
                'utilisateur',
                'adresseExpédition', // Relation avec l'adresse de livraison
                'adresseFacturation' // Relation avec l'adresse de facturation
            ])->orderBy('date_creation', 'desc');

            // Filtrer par statut si demandé
            if ($request->has('statut') && $request->statut !== 'all') {
                $query->where('statut', $request->statut);
            }

            // Filtrer par recherche si demandé
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('commande_uuid', 'like', "%{$search}%")
                        ->orWhereHas('utilisateur', function ($u) use ($search) {
                            $u->where('nom', 'like', "%{$search}%")
                                ->orWhere('prenom', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            // Récupérer les commandes avec pagination
            $perPage = $request->per_page ?? 50;
            $commandes = $query->paginate($perPage);

            // Transformation des données pour le frontend
            $commandesData = $commandes->map(function ($commande) {
                // Extraire les informations de meta_json
                $metaData = [];
                if ($commande->meta_json) {
                    try {
                        $metaData = is_string($commande->meta_json)
                            ? json_decode($commande->meta_json, true)
                            : $commande->meta_json;
                    } catch (\Exception $e) {
                        $metaData = [];
                    }
                }

                // Construire les produits
                $produits = [];
                if ($commande->produits && count($commande->produits) > 0) {
                    $produits = $commande->produits;
                } elseif (isset($metaData['produits']) && is_array($metaData['produits'])) {
                    $produits = $metaData['produits'];
                } elseif (isset($metaData['items']) && is_array($metaData['items'])) {
                    $produits = $metaData['items'];
                } else {
                    // Produit unique
                    $produits = [[
                        'id' => $commande->produit_id ?? null,
                        'nom' => $commande->titre ?? 'Produit sans nom',
                        'quantite' => $commande->quantite ?? 1,
                        'prix_unitaire' => $commande->prix_unitaire ?? 0,
                        'sous_total' => ($commande->prix_unitaire ?? 0) * ($commande->quantite ?? 1)
                    ]];
                }

                // Récupérer les photos des produits
                $photos = [];
                foreach ($produits as $produit) {
                    if (isset($produit['image_url']) && $produit['image_url']) {
                        $photos[] = $produit['image_url'];
                    }
                }

                // ✅ RÉCUPÉRER L'ADRESSE DE LIVRAISON COMPLÈTE AVEC TOUS LES DÉTAILS
                $adresseLivraison = 'Adresse non disponible';
                $adresseDetails = null; // 👈 NOUVEAU : pour stocker tous les détails

                // Essayer d'abord avec la relation adresseExpédition
                if ($commande->adresseExpédition) {
                    $adresse = $commande->adresseExpédition;
                    $adresseLivraison = $adresse->getFullAddress();

                    // 👇 AJOUT : Récupérer tous les détails de l'adresse
                    $adresseDetails = [
                        'id' => $adresse->id,
                        'utilisateur_id' => $adresse->utilisateur_id,
                        'etiquette' => $adresse->etiquette,
                        'nom_complet' => $adresse->nom_complet,
                        'telephone' => $adresse->telephone, // ✅ LE TÉLÉPHONE
                        'adresse_ligne1' => $adresse->adresse_ligne1,
                        'adresse_ligne2' => $adresse->adresse_ligne2,
                        'ville' => $adresse->ville,
                        'region' => $adresse->region,
                        'code_postal' => $adresse->code_postal,
                        'pays' => $adresse->pays,
                        'image_adress' => $adresse->image_adress, // ✅ L'IMAGE
                        'latitude' => $adresse->latitude,
                        'longitude' => $adresse->longitude,
                        'par_defaut_expedition' => $adresse->par_defaut_expedition,
                        'par_defaut_facturation' => $adresse->par_defaut_facturation,
                        'date_creation' => $adresse->date_creation,
                        'date_modification' => $adresse->date_modification,
                    ];
                }
                // Sinon, essayer de récupérer depuis meta_json
                elseif (isset($metaData['adresse_livraison'])) {
                    $adresseLivraison = $metaData['adresse_livraison'];
                    // Si l'adresse est un objet dans meta_json, on peut aussi l'utiliser
                    if (is_array($metaData['adresse_livraison'])) {
                        $adresseDetails = $metaData['adresse_livraison'];
                    }
                } elseif (isset($metaData['adresse'])) {
                    $adresseLivraison = $metaData['adresse'];
                    if (is_array($metaData['adresse'])) {
                        $adresseDetails = $metaData['adresse'];
                    }
                } elseif (isset($metaData['adresse_expedition'])) {
                    $adresseLivraison = $metaData['adresse_expedition'];
                    if (is_array($metaData['adresse_expedition'])) {
                        $adresseDetails = $metaData['adresse_expedition'];
                    }
                }

                return [
                    'id' => $commande->id,
                    'commande_uuid' => $commande->commande_uuid,
                    'statut' => $commande->statut,
                    'total' => $commande->total,
                    'sous_total' => $commande->sous_total,
                    'livraison' => $commande->livraison,
                    'devise' => $commande->devise ?? 'MGA',
                    'date_creation' => $commande->date_creation,
                    'utilisateur' => $commande->utilisateur ? [
                        'id' => $commande->utilisateur->id,
                        'nom' => $commande->utilisateur->nom,
                        'prenom' => $commande->utilisateur->prenom,
                        'email' => $commande->utilisateur->email,
                        'telephone' => $commande->utilisateur->telephone ?? 'Téléphone non disponible'
                    ] : null,
                    'produits' => $produits,
                    'photos' => $photos,
                    'adresse_livraison' => $adresseLivraison,
                    'adresse_details' => $adresseDetails, // 👈 NOUVEAU : tous les détails
                    'adresse_expedition_id' => $commande->adresse_expedition_id,
                    'meta' => $metaData,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $commandesData,
                'pagination' => [
                    'current_page' => $commandes->currentPage(),
                    'total' => $commandes->total(),
                    'per_page' => $commandes->perPage(),
                    'last_page' => $commandes->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des commandes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher les détails d'une commande
     */
    public function showCommande(string $uuid): JsonResponse
    {
        try {
            // ✅ Récupérer la commande avec les relations
            $commande = Commande::with([
                'utilisateur',
                'adresseExpédition',
                'adresseFacturation'
            ])->where('commande_uuid', $uuid)->first();

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            // Transformer les données
            $metaData = [];
            if ($commande->meta_json) {
                try {
                    $metaData = is_string($commande->meta_json)
                        ? json_decode($commande->meta_json, true)
                        : $commande->meta_json;
                } catch (\Exception $e) {
                    $metaData = [];
                }
            }

            $produits = [];
            if ($commande->produits && count($commande->produits) > 0) {
                $produits = $commande->produits;
            } elseif (isset($metaData['produits']) && is_array($metaData['produits'])) {
                $produits = $metaData['produits'];
            } elseif (isset($metaData['items']) && is_array($metaData['items'])) {
                $produits = $metaData['items'];
            }

            $photos = [];
            foreach ($produits as $produit) {
                if (isset($produit['image_url']) && $produit['image_url']) {
                    $photos[] = $produit['image_url'];
                }
            }

            // ✅ RÉCUPÉRER L'ADRESSE DE LIVRAISON
            $adresseLivraison = 'Adresse non disponible';

            if ($commande->adresseExpédition) {
                $adresseLivraison = $commande->adresseExpédition->getFullAddress();
            } elseif (isset($metaData['adresse_livraison'])) {
                $adresseLivraison = $metaData['adresse_livraison'];
            } elseif (isset($metaData['adresse'])) {
                $adresseLivraison = $metaData['adresse'];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $commande->id,
                    'commande_uuid' => $commande->commande_uuid,
                    'statut' => $commande->statut,
                    'total' => $commande->total,
                    'sous_total' => $commande->sous_total,
                    'livraison' => $commande->livraison,
                    'devise' => $commande->devise ?? 'MGA',
                    'date_creation' => $commande->date_creation,
                    'utilisateur' => $commande->utilisateur ? [
                        'id' => $commande->utilisateur->id,
                        'nom' => $commande->utilisateur->nom,
                        'prenom' => $commande->utilisateur->prenom,
                        'email' => $commande->utilisateur->email,
                        'telephone' => $commande->utilisateur->telephone ?? 'Téléphone non disponible'
                    ] : null,
                    'produits' => $produits,
                    'photos' => $photos,
                    'adresse_livraison' => $adresseLivraison,
                    'adresse_expedition_id' => $commande->adresse_expedition_id,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour le statut d'une commande
     */
    public function updateStatut(Request $request, string $uuid): JsonResponse
    {
        try {
            $commande = Commande::where('commande_uuid', $uuid)->first();

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            $validated = $request->validate([
                'statut' => 'required|in:en_attente,payee,en_traitement,expediee,terminee,annulee'
            ]);

            $commande->statut = $validated['statut'];
            $commande->save();

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'data' => $commande
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}

// namespace App\Http\Controllers\Api\Livreur;

// use App\Http\Controllers\Controller;
// use App\Models\Commande;
// use Illuminate\Http\Request;
// use Illuminate\Http\JsonResponse;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Log;

// class LivreurController extends Controller
// {
//     /**
//      * Récupérer les commandes pour le livreur
//      */
//     public function getCommandes(Request $request): JsonResponse
//     {
//         try {
//             $user = Auth::guard('admin')->user();
            
//             if (!$user) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Utilisateur non authentifié'
//                 ], 401);
//             }

//             Log::info('LivreurController - Utilisateur:', [
//                 'id' => $user->id,
//                 'email' => $user->email,
//                 'poste' => $user->poste
//             ]);

//             // ✅ Requête simplifiée sans relations problématiques
//             $query = Commande::with(['utilisateur'])
//                 ->orderBy('date_creation', 'desc');

//             // Filtrer par statut si demandé
//             if ($request->has('statut') && $request->statut !== 'all') {
//                 $query->where('statut', $request->statut);
//             }

//             // Filtrer par recherche si demandé
//             if ($request->has('search') && !empty($request->search)) {
//                 $search = $request->search;
//                 $query->where(function($q) use ($search) {
//                     $q->where('commande_uuid', 'like', "%{$search}%")
//                       ->orWhereHas('utilisateur', function($u) use ($search) {
//                           $u->where('nom', 'like', "%{$search}%")
//                             ->orWhere('prenom', 'like', "%{$search}%")
//                             ->orWhere('email', 'like', "%{$search}%");
//                       });
//                 });
//             }

//             $perPage = $request->per_page ?? 50;
//             $commandes = $query->paginate($perPage);

//             Log::info('LivreurController - Commandes trouvées:', [
//                 'count' => $commandes->total()
//             ]);

//             // Transformation des données
//             $commandesData = $commandes->map(function($commande) {
//                 // Extraire les informations de meta_json
//                 $metaData = [];
//                 $adresseLivraison = 'Adresse non disponible';
                
//                 if ($commande->meta_json) {
//                     try {
//                         $metaData = is_string($commande->meta_json) 
//                             ? json_decode($commande->meta_json, true) 
//                             : $commande->meta_json;
                        
//                         if (isset($metaData['adresse_livraison'])) {
//                             $adresseLivraison = $metaData['adresse_livraison'];
//                         } elseif (isset($metaData['adresse'])) {
//                             $adresseLivraison = $metaData['adresse'];
//                         }
//                     } catch (\Exception $e) {
//                         Log::warning('Erreur parsing meta_json:', ['error' => $e->getMessage()]);
//                     }
//                 }

//                 // Construire les produits
//                 $produits = [];
//                 $photos = [];
                
//                 if ($commande->produits && count($commande->produits) > 0) {
//                     $produits = $commande->produits;
//                 } elseif (isset($metaData['produits']) && is_array($metaData['produits'])) {
//                     $produits = $metaData['produits'];
//                 } elseif (isset($metaData['items']) && is_array($metaData['items'])) {
//                     $produits = $metaData['items'];
//                 } else {
//                     $produits = [[
//                         'id' => $commande->produit_id ?? null,
//                         'nom' => $commande->titre ?? 'Produit sans nom',
//                         'quantite' => $commande->quantite ?? 1,
//                         'prix_unitaire' => $commande->prix_unitaire ?? 0,
//                         'sous_total' => ($commande->prix_unitaire ?? 0) * ($commande->quantite ?? 1)
//                     ]];
//                 }

//                 // Récupérer les photos des produits
//                 foreach ($produits as $produit) {
//                     if (isset($produit['image_url']) && $produit['image_url']) {
//                         $photos[] = $produit['image_url'];
//                     }
//                 }

//                 return [
//                     'id' => $commande->id,
//                     'commande_uuid' => $commande->commande_uuid,
//                     'statut' => $commande->statut,
//                     'total' => (float) $commande->total,
//                     'sous_total' => (float) $commande->sous_total,
//                     'livraison' => (float) $commande->livraison,
//                     'devise' => $commande->devise ?? 'MGA',
//                     'date_creation' => $commande->date_creation,
//                     'utilisateur' => $commande->utilisateur ? [
//                         'id' => $commande->utilisateur->id,
//                         'nom' => $commande->utilisateur->nom ?? '',
//                         'prenom' => $commande->utilisateur->prenom ?? '',
//                         'email' => $commande->utilisateur->email ?? '',
//                         'telephone' => $commande->utilisateur->telephone ?? 'Téléphone non disponible'
//                     ] : null,
//                     'produits' => $produits,
//                     'photos' => $photos,
//                     'adresse_livraison' => $adresseLivraison,
//                     'adresse_expedition_id' => $commande->adresse_expedition_id,
//                     'meta' => $metaData,
//                 ];
//             });

//             return response()->json([
//                 'success' => true,
//                 'data' => $commandesData,
//                 'pagination' => [
//                     'current_page' => $commandes->currentPage(),
//                     'total' => $commandes->total(),
//                     'per_page' => $commandes->perPage(),
//                     'last_page' => $commandes->lastPage(),
//                 ]
//             ]);

//         } catch (\Exception $e) {
//             Log::error('LivreurController - Erreur:', [
//                 'message' => $e->getMessage(),
//                 'file' => $e->getFile(),
//                 'line' => $e->getLine(),
//                 'trace' => $e->getTraceAsString()
//             ]);
            
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur: ' . $e->getMessage()
//             ], 500);
//         }
//     }

//     /**
//      * Afficher les détails d'une commande
//      */
//     public function showCommande(string $uuid): JsonResponse
//     {
//         try {
//             // ✅ Requête simplifiée sans adresseExpédition
//             $commande = Commande::with(['utilisateur'])
//                 ->where('commande_uuid', $uuid)
//                 ->first();

//             if (!$commande) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Commande non trouvée'
//                 ], 404);
//             }

//             // Transformer les données
//             $metaData = [];
//             $adresseLivraison = 'Adresse non disponible';
            
//             if ($commande->meta_json) {
//                 try {
//                     $metaData = is_string($commande->meta_json) 
//                         ? json_decode($commande->meta_json, true) 
//                         : $commande->meta_json;
                    
//                     if (isset($metaData['adresse_livraison'])) {
//                         $adresseLivraison = $metaData['adresse_livraison'];
//                     } elseif (isset($metaData['adresse'])) {
//                         $adresseLivraison = $metaData['adresse'];
//                     }
//                 } catch (\Exception $e) {
//                     // Ignorer
//                 }
//             }

//             $produits = [];
//             $photos = [];
            
//             if ($commande->produits && count($commande->produits) > 0) {
//                 $produits = $commande->produits;
//             } elseif (isset($metaData['produits']) && is_array($metaData['produits'])) {
//                 $produits = $metaData['produits'];
//             } elseif (isset($metaData['items']) && is_array($metaData['items'])) {
//                 $produits = $metaData['items'];
//             } else {
//                 $produits = [[
//                     'id' => $commande->produit_id ?? null,
//                     'nom' => $commande->titre ?? 'Produit sans nom',
//                     'quantite' => $commande->quantite ?? 1,
//                     'prix_unitaire' => $commande->prix_unitaire ?? 0,
//                 ]];
//             }

//             foreach ($produits as $produit) {
//                 if (isset($produit['image_url']) && $produit['image_url']) {
//                     $photos[] = $produit['image_url'];
//                 }
//             }

//             return response()->json([
//                 'success' => true,
//                 'data' => [
//                     'id' => $commande->id,
//                     'commande_uuid' => $commande->commande_uuid,
//                     'statut' => $commande->statut,
//                     'total' => (float) $commande->total,
//                     'sous_total' => (float) $commande->sous_total,
//                     'livraison' => (float) $commande->livraison,
//                     'devise' => $commande->devise ?? 'MGA',
//                     'date_creation' => $commande->date_creation,
//                     'utilisateur' => $commande->utilisateur ? [
//                         'id' => $commande->utilisateur->id,
//                         'nom' => $commande->utilisateur->nom ?? '',
//                         'prenom' => $commande->utilisateur->prenom ?? '',
//                         'email' => $commande->utilisateur->email ?? '',
//                         'telephone' => $commande->utilisateur->telephone ?? 'Téléphone non disponible'
//                     ] : null,
//                     'produits' => $produits,
//                     'photos' => $photos,
//                     'adresse_livraison' => $adresseLivraison,
//                     'adresse_expedition_id' => $commande->adresse_expedition_id,
//                 ]
//             ]);

//         } catch (\Exception $e) {
//             Log::error('LivreurController showCommande - Erreur:', [
//                 'message' => $e->getMessage(),
//                 'file' => $e->getFile(),
//                 'line' => $e->getLine()
//             ]);
            
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur: ' . $e->getMessage()
//             ], 500);
//         }
//     }

//     /**
//      * Mettre à jour le statut d'une commande
//      */
//     public function updateStatut(Request $request, string $uuid): JsonResponse
//     {
//         try {
//             $commande = Commande::where('commande_uuid', $uuid)->first();

//             if (!$commande) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Commande non trouvée'
//                 ], 404);
//             }

//             $validated = $request->validate([
//                 'statut' => 'required|in:en_attente,payee,en_traitement,expediee,terminee,annulee'
//             ]);

//             $commande->statut = $validated['statut'];
//             $commande->save();

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Statut mis à jour avec succès',
//                 'data' => $commande
//             ]);

//         } catch (\Exception $e) {
//             Log::error('LivreurController updateStatut - Erreur:', [
//                 'message' => $e->getMessage(),
//                 'file' => $e->getFile(),
//                 'line' => $e->getLine()
//             ]);
            
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur: ' . $e->getMessage()
//             ], 500);
//         }
//     }
// }