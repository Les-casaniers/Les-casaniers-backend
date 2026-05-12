<?php

namespace App\Http\Controllers\Api\Commandes;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\CommandeLigne;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Commandes",
 *     description="Gestion des commandes clients - Endpoints pour les commandes"
 * )
 */
class CommandeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/commandes",
     *     summary="Lister mes commandes",
     *     description="Récupère la liste de toutes les commandes de l'utilisateur connecté",
     *     operationId="getCommandesList",
     *     tags={"Commandes"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des commandes récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Commandes récupérées")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(Request $request)
    {
        try {
            $commandes = Commande::with('lignes.produit')
                ->where('utilisateur_id', $request->user()->id)
                ->orderBy('date_creation', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $commandes,
                'message' => 'Commandes récupérées'
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
     *     path="/api/commandes",
     *     summary="Créer une nouvelle commande",
     *     description="Crée une commande avec les produits selectionnés",
     *     operationId="storeCommande",
     *     tags={"Commandes"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"lignes","adresse_livraison","adresse_facturation"},
     *             @OA\Property(
     *                 property="lignes",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"produit_id","quantite"},
     *                     @OA\Property(property="produit_id", type="integer", example=1),
     *                     @OA\Property(property="quantite", type="integer", example=2)
     *                 )
     *             ),
     *             @OA\Property(property="adresse_livraison", type="string", example="Lot II J 83 Antananarivo"),
     *             @OA\Property(property="adresse_facturation", type="string", example="Lot II J 83 Antananarivo"),
     *             @OA\Property(property="notes", type="string", example="Appeler avant livraison")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Commande créée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Commande créée")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lignes' => 'required|array|min:1',
            'lignes.*.produit_id' => 'required|exists:produits,id',
            'lignes.*.quantite' => 'required|integer|min:1',
            'adresse_livraison' => 'required|string',
            'adresse_facturation' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $sousTotal = 0;
            $lignesData = [];

            foreach ($request->lignes as $ligne) {
                $produit = Produit::findOrFail($ligne['produit_id']);
                
                if ($produit->quantite_stock < $ligne['quantite']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant pour: {$produit->nom}"
                    ], 400);
                }

                $prix = $produit->prix_promo ?? $produit->prix;
                $totalLigne = $prix * $ligne['quantite'];
                $sousTotal += $totalLigne;

                $lignesData[] = [
                    'produit_id' => $produit->id,
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $prix,
                    'total_ligne' => $totalLigne
                ];
            }

            $livraison = $sousTotal > 100000 ? 0 : 5000;
            $total = $sousTotal + $livraison;

            $commande = Commande::create([
                'utilisateur_id' => $request->user()->id,
                'statut' => 'en_attente',
                'sous_total' => $sousTotal,
                'livraison' => $livraison,
                'total' => $total,
                'devise' => 'MGA',
                'adresse_livraison' => $request->adresse_livraison,
                'adresse_facturation' => $request->adresse_facturation,
                'notes' => $request->notes,
                'date_creation' => now(),
                'date_modification' => now()
            ]);

            foreach ($lignesData as $ligne) {
                $commande->lignes()->create($ligne);
                
                $produit = Produit::find($ligne['produit_id']);
                $produit->quantite_stock -= $ligne['quantite'];
                $produit->save();
            }

            DB::commit();

            // ICI - C'EST LÀ QUE VOUS DEVEZ MODIFIER
            return response()->json([
                'success' => true,
                'data' => [
                    'commande' => $commande,
                    'lignes' => $lignesData
                ],
                'message' => 'Commande créée'
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
     *     path="/api/commandes/{id}",
     *     summary="Détails d'une commande",
     *     description="Affiche les détails d'une commande spécifique",
     *     operationId="showCommande",
     *     tags={"Commandes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la commande",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détails de la commande"),
     *     @OA\Response(response=404, description="Commande non trouvée")
     * )
     */
    public function show(Request $request, $id)
    {
        try {
            $commande = Commande::with(['lignes.produit'])
                ->where('utilisateur_id', $request->user()->id)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $commande
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/commandes/{id}/cancel",
     *     summary="Annuler une commande",
     *     description="Annule une commande si elle est encore en attente ou payée",
     *     operationId="cancelCommande",
     *     tags={"Commandes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la commande à annuler",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Commande annulée"),
     *     @OA\Response(response=400, description="Commande non annulable"),
     *     @OA\Response(response=404, description="Commande non trouvée")
     * )
     */
    public function cancel(Request $request, $id)
    {
        try {
            $commande = Commande::where('id', $id)
                ->where('utilisateur_id', $request->user()->id)
                ->firstOrFail();

            if (!in_array($commande->statut, ['en_attente', 'payee'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande ne peut pas être annulée'
                ], 400);
            }

            $commande->statut = 'annulee';
            $commande->date_modification = now();
            $commande->save();

            foreach ($commande->lignes as $ligne) {
                $produit = Produit::find($ligne->produit_id);
                if ($produit) {
                    $produit->quantite_stock += $ligne->quantite;
                    $produit->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Commande annulée'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}