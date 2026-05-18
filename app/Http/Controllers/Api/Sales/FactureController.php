<?php

// namespace App\Http\Controllers\Api\Sales;

// use App\Http\Controllers\Controller;
// use App\Models\Commande;
// use App\Models\Facture;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Validation\ValidationException;
// use Throwable;

// class FactureController extends Controller
// {
//     public function __construct()
//     {
//         $this->middleware('auth:sanctum');
//     }

//     /**
//      * Récupérer les factures de l'utilisateur
//      */
//     public function index(Request $request)
//     {
//         try {
//             $factures = Facture::whereHas('commande', function($q) use ($request) {
//                 $q->where('utilisateur_id', $request->user()->id);
//             })->orderBy('date_creation', 'desc')->get();

//             return response()->json([
//                 'success' => true,
//                 'data' => $factures
//             ], 200);
//         } catch (Throwable $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur lors du chargement des factures: ' . $e->getMessage()
//             ], 500);
//         }
//     }

//     /**
//      * Récupérer une facture spécifique
//      */
//     public function show(Request $request, $id)
//     {
//         try {
//             $facture = Facture::where('id', $id)
//                 ->whereHas('commande', function($q) use ($request) {
//                     $q->where('utilisateur_id', $request->user()->id);
//                 })->first();

//             if (!$facture) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Facture non trouvée'
//                 ], 404);
//             }

//             return response()->json([
//                 'success' => true,
//                 'data' => $facture
//             ], 200);
//         } catch (Throwable $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur serveur'
//             ], 500);
//         }
//     }

//     /**
//      * Créer une facture après paiement
//      */
//     public function generate(Request $request)
//     {
//         try {
//             \Illuminate\Support\Facades\Log::info('=== GÉNÉRATION FACTURE ===');
//             \Illuminate\Support\Facades\Log::info($request->all());
            
//             $validated = $request->validate([
//                 'commande_uuid' => 'required|exists:commandes,commande_uuid',
//                 'methode_paiement' => 'required|string|in:carte,mvola,airtel,orange_money,especes'
//             ]);

//             $commande = Commande::where('commande_uuid', $validated['commande_uuid'])
//                 ->where('utilisateur_id', $request->user()->id)
//                 ->first();

//             if (!$commande) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Commande non trouvée'
//                 ], 404);
//             }

//             // Vérifier si une facture existe déjà
//             $existingFacture = Facture::where('commande_id', $commande->id)->first();
//             if ($existingFacture) {
//                 return response()->json([
//                     'success' => true,
//                     'message' => 'Facture déjà existante',
//                     'data' => $existingFacture
//                 ], 200);
//             }

//             // Générer le numéro de facture
//             $lastFacture = Facture::orderBy('id', 'desc')->first();
//             $lastNumber = $lastFacture ? intval(substr($lastFacture->facture_ref, -4)) : 0;
//             $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
//             $factureRef = 'FAC-' . date('Y') . '-' . $newNumber;

//             // Créer la facture avec les colonnes adaptées
//             $facture = Facture::create([
//                 'commande_id' => $commande->id,
//                 'facture_ref' => $factureRef,
//                 'statut' => 'emise',  // brouillon, emise, payee, annulee
//                 'montant_total' => $commande->total,
//                 'devise' => $commande->devise ?? 'MGA',
//                 'methode_paiement' => $validated['methode_paiement'],
//                 'date_emission' => now(),
//                 'date_creation' => now(),
//                 'date_modification' => now(),
//             ]);

//             \Illuminate\Support\Facades\Log::info('Facture créée avec ID: ' . $facture->id);

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Facture générée avec succès',
//                 'data' => $facture
//             ], 201);

//         } catch (ValidationException $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur de validation',
//                 'errors' => $e->errors()
//             ], 422);
//         } catch (Throwable $e) {
//             \Illuminate\Support\Facades\Log::error('Erreur génération facture: ' . $e->getMessage());
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur serveur: ' . $e->getMessage()
//             ], 500);
//         }
//     }

//     /**
//      * Marquer une facture comme payée
//      */
//     public function markAsPaid(Request $request, $id)
//     {
//         try {
//             $facture = Facture::where('id', $id)
//                 ->whereHas('commande', function($q) use ($request) {
//                     $q->where('utilisateur_id', $request->user()->id);
//                 })->first();

//             if (!$facture) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Facture non trouvée'
//                 ], 404);
//             }

//             DB::transaction(function () use ($facture) {
//                 $facture->update([
//                     'statut' => 'payee',
//                     'date_paiement' => now(),
//                     'date_modification' => now(),
//                 ]);

//                 // Mettre à jour le statut de la commande
//                 $facture->commande->update(['statut' => 'payee']);
//             });

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Paiement confirmé avec succès',
//                 'data' => $facture
//             ], 200);

//         } catch (Throwable $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur serveur: ' . $e->getMessage()
//             ], 500);
//         }
//     }

//     /**
//      * Admin - Liste toutes les factures
//      */
//     public function adminIndex(Request $request)
//     {
//         try {
//             $factures = Facture::with('commande')
//                 ->orderBy('date_creation', 'desc')
//                 ->get();

//             return response()->json([
//                 'success' => true,
//                 'data' => $factures
//             ], 200);
//         } catch (Throwable $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur lors du chargement des factures'
//             ], 500);
//         }
//     }

//     /**
//      * Admin - Créer une facture
//      */
//     public function adminStore(Request $request)
//     {
//         try {
//             $validated = $request->validate([
//                 'commande_id' => 'required|exists:commandes,id',
//                 'facture_ref' => 'required|string|unique:factures',
//                 'statut' => 'required|in:brouillon,emise,payee,annulee',
//                 'montant_total' => 'required|numeric',
//                 'devise' => 'required|string|size:3',
//                 'methode_paiement' => 'nullable|string',
//                 'date_emission' => 'nullable|date',
//             ]);

//             $facture = Facture::create([
//                 'commande_id' => $validated['commande_id'],
//                 'facture_ref' => $validated['facture_ref'],
//                 'statut' => $validated['statut'],
//                 'montant_total' => $validated['montant_total'],
//                 'devise' => $validated['devise'],
//                 'methode_paiement' => $validated['methode_paiement'] ?? null,
//                 'date_emission' => $validated['date_emission'] ?? now(),
//                 'date_creation' => now(),
//                 'date_modification' => now(),
//             ]);

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Facture créée avec succès',
//                 'data' => $facture
//             ], 201);
//         } catch (ValidationException $e) {
//             return response()->json([
//                 'success' => false,
//                 'errors' => $e->errors()
//             ], 422);
//         } catch (Throwable $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur serveur'
//             ], 500);
//         }
//     }

//     /**
//      * Admin - Voir une facture
//      */
//     public function adminShow($id)
//     {
//         try {
//             $facture = Facture::with('commande')->findOrFail($id);
            
//             return response()->json([
//                 'success' => true,
//                 'data' => $facture
//             ], 200);
//         } catch (Throwable $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Facture non trouvée'
//             ], 404);
//         }
//     }

//     /**
//      * Admin - Marquer une facture comme émise
//      */
//     public function adminEmit($id)
//     {
//         try {
//             $facture = Facture::findOrFail($id);
//             $facture->update([
//                 'statut' => 'emise',
//                 'date_modification' => now(),
//             ]);

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Facture émise avec succès',
//                 'data' => $facture
//             ], 200);
//         } catch (Throwable $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur serveur'
//             ], 500);
//         }
//     }

//     /**
//      * Admin - Marquer une facture comme payée
//      */
//     public function adminMarkPaid($id)
//     {
//         try {
//             $facture = Facture::findOrFail($id);
//             $facture->update([
//                 'statut' => 'payee',
//                 'date_paiement' => now(),
//                 'date_modification' => now(),
//             ]);

//             // Mettre à jour le statut de la commande associée
//             if ($facture->commande) {
//                 $facture->commande->update(['statut' => 'payee']);
//             }

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Facture marquée comme payée',
//                 'data' => $facture
//             ], 200);
//         } catch (Throwable $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur serveur'
//             ], 500);
//         }
//     }

//     /**
//      * Admin - Annuler une facture
//      */
//     public function adminCancel($id)
//     {
//         try {
//             $facture = Facture::findOrFail($id);
//             $facture->update([
//                 'statut' => 'annulee',
//                 'date_modification' => now(),
//             ]);

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Facture annulée',
//                 'data' => $facture
//             ], 200);
//         } catch (Throwable $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur serveur'
//             ], 500);
//         }
//     }

//     /**
//      * Télécharger le PDF d'une facture
//      */
//     public function download($id)
//     {
//         try {
//             $facture = Facture::findOrFail($id);
            
//             // Générer le PDF (à implémenter avec une bibliothèque comme DomPDF)
//             // Pour l'instant, retourner les données JSON
//             return response()->json([
//                 'success' => true,
//                 'message' => 'Fonctionnalité de téléchargement PDF à implémenter',
//                 'data' => $facture
//             ], 200);
//         } catch (Throwable $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur lors du téléchargement'
//             ], 500);
//         }
//     }

//     /**
//      * Admin - Télécharger le PDF d'une facture
//      */
//     public function adminDownload($id)
//     {
//         return $this->download($id);
//     }
// }
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class FactureController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Récupérer les factures de l'utilisateur
     */
    public function index(Request $request)
    {
        try {
            $factures = Facture::whereHas('commande', function($q) use ($request) {
                $q->where('utilisateur_id', $request->user()->id);
            })->with('commande')->orderBy('date_creation', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $factures
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des factures: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer une facture spécifique
     */
    public function show(Request $request, $id)
    {
        try {
            $facture = Facture::where('id', $id)
                ->whereHas('commande', function($q) use ($request) {
                    $q->where('utilisateur_id', $request->user()->id);
                })->with('commande')->first();

            if (!$facture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture non trouvée'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $facture
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Générer une facture PDF
     */
    private function generateFacturePdf(Facture $facture, Commande $commande)
    {
        // Parser les produits depuis meta_json (gère les deux types: string ou array)
        $produits = [];
        
        if ($commande->meta_json) {
            // Vérifier le type de meta_json
            if (is_string($commande->meta_json)) {
                // C'est une chaîne JSON, on la décode
                $meta = json_decode($commande->meta_json, true);
            } elseif (is_array($commande->meta_json)) {
                // C'est déjà un tableau, on l'utilise directement
                $meta = $commande->meta_json;
            } else {
                $meta = [];
            }
            
            if (isset($meta['produits']) && is_array($meta['produits'])) {
                $produits = $meta['produits'];
            } elseif (isset($meta['items']) && is_array($meta['items'])) {
                $produits = $meta['items'];
            }
        }
        
        // Si pas de produits dans meta_json, créer un produit par défaut
        if (empty($produits) && $commande->titre) {
            $produits = [[
                'nom' => $commande->titre,
                'quantite' => (int) $commande->quantite,
                'prix_unitaire' => (float) $commande->prix_unitaire,
                'sous_total' => (float) $commande->prix_unitaire * (int) $commande->quantite
            ]];
        }
        
        // Si toujours pas de produits, créer un produit par défaut
        if (empty($produits)) {
            $produits = [[
                'nom' => 'Produit',
                'quantite' => 1,
                'prix_unitaire' => (float) $commande->total,
                'sous_total' => (float) $commande->total
            ]];
        }
        
        $data = [
            'facture' => $facture,
            'commande' => $commande,
            'produits' => $produits,
            'date_emission' => $facture->date_emission,
            'client' => $commande->utilisateur,
        ];
        
        try {
            $pdf = Pdf::loadView('pdf.facture', $data);
            $pdfPath = 'factures/' . $facture->facture_ref . '.pdf';
            
            // Sauvegarder le PDF
            Storage::disk('public')->put($pdfPath, $pdf->output());
            
            return $pdfPath;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur génération PDF: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Créer une facture après paiement
     */
    public function generate(Request $request)
    {
        try {
            \Illuminate\Support\Facades\Log::info('=== GÉNÉRATION FACTURE ===');
            \Illuminate\Support\Facades\Log::info($request->all());
            
            $validated = $request->validate([
                'commande_uuid' => 'required|exists:commandes,commande_uuid',
                'methode_paiement' => 'required|string|in:carte,mvola,airtel,orange_money,especes'
            ]);

            $commande = Commande::where('commande_uuid', $validated['commande_uuid'])
                ->where('utilisateur_id', $request->user()->id)
                ->first();

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            // Vérifier si une facture existe déjà
            $existingFacture = Facture::where('commande_id', $commande->id)->first();
            if ($existingFacture) {
                return response()->json([
                    'success' => true,
                    'message' => 'Facture déjà existante',
                    'data' => $existingFacture
                ], 200);
            }

            // Générer le numéro de facture
            $lastFacture = Facture::orderBy('id', 'desc')->first();
            $lastNumber = $lastFacture ? intval(substr($lastFacture->facture_ref, -4)) : 0;
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            $factureRef = 'FAC-' . date('Y') . '-' . $newNumber;

            // Créer la facture
            $facture = Facture::create([
                'commande_id' => $commande->id,
                'facture_ref' => $factureRef,
                'statut' => 'emise',
                'montant_total' => $commande->total,
                'devise' => $commande->devise ?? 'MGA',
                'methode_paiement' => $validated['methode_paiement'],
                'date_emission' => now(),
                'date_creation' => now(),
                'date_modification' => now(),
            ]);

            // Générer le PDF
            $pdfPath = $this->generateFacturePdf($facture, $commande);
            if ($pdfPath) {
                $facture->update(['pdf_path' => $pdfPath]);
            }

            \Illuminate\Support\Facades\Log::info('Facture créée avec ID: ' . $facture->id);

            return response()->json([
                'success' => true,
                'message' => 'Facture générée avec succès',
                'data' => $facture
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Erreur génération facture: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer une facture comme payée
     */
    public function markAsPaid(Request $request, $id)
    {
        try {
            $facture = Facture::where('id', $id)
                ->whereHas('commande', function($q) use ($request) {
                    $q->where('utilisateur_id', $request->user()->id);
                })->first();

            if (!$facture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture non trouvée'
                ], 404);
            }

            DB::transaction(function () use ($facture) {
                $facture->update([
                    'statut' => 'payee',
                    'date_paiement' => now(),
                    'date_modification' => now(),
                ]);

                $facture->commande->update(['statut' => 'payee']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Paiement confirmé avec succès',
                'data' => $facture
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger le PDF d'une facture
     */
    public function download($id)
    {
        try {
            $facture = Facture::findOrFail($id);
            
            if (!$facture->pdf_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'PDF non disponible pour cette facture'
                ], 404);
            }
            
            $pdfPath = storage_path('app/public/' . $facture->pdf_path);
            
            if (!file_exists($pdfPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier PDF non trouvé'
                ], 404);
            }
            
            return response()->download($pdfPath, $facture->facture_ref . '.pdf', [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une facture
     */
    public function destroy(Request $request, $id)
    {
        try {
            $facture = Facture::where('id', $id)
                ->whereHas('commande', function($q) use ($request) {
                    $q->where('utilisateur_id', $request->user()->id);
                })->first();

            if (!$facture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture non trouvée'
                ], 404);
            }

            // Supprimer le fichier PDF
            if ($facture->pdf_path) {
                $pdfPath = storage_path('app/public/' . $facture->pdf_path);
                if (file_exists($pdfPath)) {
                    unlink($pdfPath);
                }
            }

            $facture->delete();

            return response()->json([
                'success' => true,
                'message' => 'Facture supprimée avec succès'
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== MÉTHODES ADMIN ====================

        /**
     * Admin - Récupérer toutes les factures
     */
    public function adminIndex(Request $request)
    {
        try {
            $factures = Facture::with(['commande.utilisateur'])
                ->orderBy('date_creation', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $factures
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des factures: ' . $e->getMessage()
            ], 500);
        }
    }

        /**
     * Admin - Voir une facture
     */
    public function adminShow(Request $request, $id)
    {
        try {
            $facture = Facture::with(['commande.utilisateur'])->find($id);

            if (!$facture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture non trouvée'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $facture
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Admin - Émettre une facture (brouillon -> emise)
     */
    public function adminEmit(Request $request, $id)
    {
        try {
            $facture = Facture::find($id);

            if (!$facture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture non trouvée'
                ], 404);
            }

            if ($facture->statut !== 'brouillon') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seules les factures en brouillon peuvent être émises'
                ], 422);
            }

            $facture->update([
                'statut' => 'emise',
                'date_modification' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Facture émise avec succès',
                'data' => $facture
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin - Marquer une facture comme payée (emise -> payee)
     */
    public function adminMarkPaid(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'methode' => 'nullable|string|max:255'
            ]);

            $facture = Facture::find($id);

            if (!$facture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture non trouvée'
                ], 404);
            }

            if ($facture->statut !== 'emise') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seules les factures émises peuvent être marquées comme payées'
                ], 422);
            }

            DB::transaction(function () use ($facture, $validated) {
                $facture->update([
                    'statut' => 'payee',
                    'methode_paiement' => $validated['methode'] ?? $facture->methode_paiement,
                    'date_paiement' => now(),
                    'date_modification' => now(),
                ]);

                // Mettre à jour le statut de la commande associée
                if ($facture->commande) {
                    $facture->commande->update(['statut' => 'payee']);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Facture marquée comme payée',
                'data' => $facture
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin - Annuler une facture (brouillon/emise -> annulee)
     */
    public function adminCancel(Request $request, $id)
    {
        try {
            $facture = Facture::find($id);

            if (!$facture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture non trouvée'
                ], 404);
            }

            if (!in_array($facture->statut, ['brouillon', 'emise'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seules les factures en brouillon ou émises peuvent être annulées'
                ], 422);
            }

            $facture->update([
                'statut' => 'annulee',
                'date_modification' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Facture annulée avec succès',
                'data' => $facture
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

     /**
     * Admin - Supprimer définitivement une facture
     */
    public function adminDestroy(Request $request, $id)
    {
        try {
            $facture = Facture::find($id);

            if (!$facture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture non trouvée'
                ], 404);
            }

            // Supprimer le fichier PDF associé
            if ($facture->pdf_path) {
                $pdfPath = storage_path('app/public/' . $facture->pdf_path);
                if (file_exists($pdfPath)) {
                    unlink($pdfPath);
                }
            }

            $facture->delete();

            return response()->json([
                'success' => true,
                'message' => 'Facture supprimée avec succès'
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

        /**
     * Admin - Télécharger le PDF d'une facture
     */
    public function adminDownload(Request $request, $id)
    {
        try {
            $facture = Facture::find($id);

            if (!$facture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture non trouvée'
                ], 404);
            }

            if (!$facture->pdf_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'PDF non disponible pour cette facture'
                ], 404);
            }

            $pdfPath = storage_path('app/public/' . $facture->pdf_path);

            if (!file_exists($pdfPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier PDF non trouvé'
                ], 404);
            }

            return response()->download($pdfPath, $facture->facture_ref . '.pdf', [
                'Content-Type' => 'application/pdf',
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement: ' . $e->getMessage()
            ], 500);
        }
    }

}
