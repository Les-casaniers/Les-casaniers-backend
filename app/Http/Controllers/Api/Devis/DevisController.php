<?php

namespace App\Http\Controllers\Api\Devis;

use App\Http\Controllers\Controller;
use App\Models\Devis;
use App\Models\Panier;
use App\Models\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

/**
 * @OA\Tag(
 *     name="Devis",
 *     description="Gestion des devis"
 * )
 */
class DevisController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/devis",
     *     summary="Lister mes devis (client)",
     *     tags={"Devis"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Liste des devis")
     * )
     */
    public function index(Request $request)
    {
        try {
            $devis = Devis::where('utilisateur_id', $request->user()->id)
                ->orderBy('date_creation', 'desc')
                ->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => $devis,
                'message' => 'Devis récupérés'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Post(
     *     path="/api/devis/creer",
     *     summary="Créer un devis à partir du panier",
     *     tags={"Devis"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom_client", "email_client"},
     *             @OA\Property(property="nom_client", type="string", example="Jean Dupont"),
     *             @OA\Property(property="email_client", type="string", example="jean@email.com"),
     *             @OA\Property(property="telephone_client", type="string", example="0341234567"),
     *             @OA\Property(property="note", type="string", example="Besoin d'une livraison rapide")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Devis créé")
     * )
     */
    public function creer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom_client' => 'required|string|max:190',
            'email_client' => 'required|email|max:190',
            'telephone_client' => 'nullable|string|max:30',
            'note' => 'nullable|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        
        try {
            // Récupérer le panier actif de l'utilisateur
            $panier = Panier::where('utilisateur_id', $request->user()->id)
                ->where('statut', 'actif')
                ->with('items.produit')
                ->first();
            
            if (!$panier || $panier->items->count() == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre panier est vide'
                ], 400);
            }
            
            // Calculer le montant total
            $montantTotal = $panier->items->sum(function($item) {
                return $item->prix_unitaire * $item->quantite;
            });
            
            // Créer le devis
            $devis = Devis::create([
                'utilisateur_id' => $request->user()->id,
                'panier_id' => $panier->id,
                'configuration_id' => $request->configuration_id,
                'statut' => 'brouillon',
                'nom_client' => $request->nom_client,
                'email_client' => $request->email_client,
                'telephone_client' => $request->telephone_client,
                'note' => $request->note,
                'montant_total' => $montantTotal,
                'devise' => 'MGA',
                'date_creation' => now(),
                'date_modification' => now()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'devis' => $devis,
                    'panier' => $panier,
                    'items' => $panier->items,
                    'total' => $montantTotal
                ],
                'message' => 'Devis créé avec succès'
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/devis/{id}",
     *     summary="Voir un devis",
     *     tags={"Devis"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détails du devis")
     * )
     */
    public function show(Request $request, $id)
    {
        try {
            $devis = Devis::with(['panier.items.produit', 'configuration'])
                ->where('utilisateur_id', $request->user()->id)
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $devis,
                'message' => 'Détails du devis'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Devis non trouvé'
            ], 404);
        }
    }
    
    /**
     * @OA\Put(
     *     path="/api/devis/{id}/envoyer",
     *     summary="Envoyer un devis (changer statut à 'envoye')",
     *     tags={"Devis"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Devis envoyé")
     * )
     */
    public function envoyer(Request $request, $id)
    {
        try {
            $devis = Devis::where('utilisateur_id', $request->user()->id)
                ->findOrFail($id);
            
            if ($devis->statut != 'brouillon') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce devis ne peut pas être envoyé'
                ], 400);
            }
            
            $devis->statut = 'envoye';
            $devis->date_modification = now();
            $devis->save();
            
            // Ici vous pouvez ajouter l'envoi d'email
            // Mail::to($devis->email_client)->send(new DevisEnvoye($devis));
            
            return response()->json([
                'success' => true,
                'data' => $devis,
                'message' => 'Devis envoyé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Delete(
     *     path="/api/devis/{id}",
     *     summary="Supprimer un devis (uniquement brouillon)",
     *     tags={"Devis"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Devis supprimé")
     * )
     */
    public function destroy(Request $request, $id)
    {
        try {
            $devis = Devis::where('utilisateur_id', $request->user()->id)
                ->where('statut', 'brouillon')
                ->findOrFail($id);
            
            $devis->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Devis supprimé'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Devis non trouvé ou non supprimable'
            ], 404);
        }
    }
    
    // ========== ADMIN ROUTES ==========
    
    /**
     * @OA\Get(
     *     path="/api/admin/devis",
     *     summary="Lister tous les devis (Admin)",
     *     tags={"Devis - Admin"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Liste des devis")
     * )
     */
    public function adminList(Request $request)
    {
        try {
            $devis = Devis::with('utilisateur')
                ->orderBy('date_creation', 'desc')
                ->paginate(20);
            
            return response()->json([
                'success' => true,
                'data' => $devis
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Put(
     *     path="/api/admin/devis/{id}/statut",
     *     summary="Changer le statut d'un devis (Admin)",
     *     tags={"Devis - Admin"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"statut"},
     *             @OA\Property(property="statut", type="string", enum={"brouillon","envoye","accepte","refuse","expire"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Statut modifié")
     * )
     */
    public function updateStatut(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:brouillon,envoye,accepte,refuse,expire'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $devis = Devis::findOrFail($id);
            $devis->statut = $request->statut;
            $devis->date_modification = now();
            $devis->save();
            
            return response()->json([
                'success' => true,
                'data' => $devis,
                'message' => 'Statut mis à jour'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Devis non trouvé'
            ], 404);
        }
    }
}