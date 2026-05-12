<?php

namespace App\Http\Controllers\Api\AvisClient;

use App\Http\Controllers\Controller;
use App\Models\AvisClient;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Avis Clients",
 *     description="Gestion des avis clients sur les produits"
 * )
 */
class AvisClientController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/produits/{produitId}/avis",
     *     summary="Lister les avis d'un produit",
     *     tags={"Avis Clients"},
     *     @OA\Parameter(
     *         name="produitId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Liste des avis")
     * )
     */
    public function getAvisByProduit($produitId)
    {
        try {
            $avis = AvisClient::with('utilisateur')
                ->where('produit_id', $produitId)
                ->where('publie', true)
                ->orderBy('date_creation', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $avis,
                'message' => 'Avis récupérés avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/mes-avis",
     *     summary="Lister mes avis (client connecté)",
     *     tags={"Avis Clients"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Mes avis")
     * )
     */
    public function getMesAvis(Request $request)
    {
        try {
            $avis = AvisClient::with('produit')
                ->where('utilisateur_id', $request->user()->id)
                ->orderBy('date_creation', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $avis,
                'message' => 'Vos avis récupérés avec succès'
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
     *     path="/api/avis",
     *     summary="Ajouter un avis sur un produit",
     *     tags={"Avis Clients"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"produit_id", "note"},
     *             @OA\Property(property="produit_id", type="integer", example=1),
     *             @OA\Property(property="note", type="integer", minimum=1, maximum=5, example=5),
     *             @OA\Property(property="titre", type="string", example="Excellent produit !"),
     *             @OA\Property(property="corps", type="string", example="Je suis très satisfait de ce produit...")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Avis ajouté"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'produit_id' => 'required|exists:produits,id',
            'note' => 'required|integer|min:1|max:5',
            'titre' => 'nullable|string|max:190',
            'corps' => 'nullable|string|max:5000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Vérifier si l'utilisateur a déjà donné un avis sur ce produit
            $existingAvis = AvisClient::where('produit_id', $request->produit_id)
                ->where('utilisateur_id', $request->user()->id)
                ->first();

            if ($existingAvis) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous avez déjà donné un avis sur ce produit'
                ], 400);
            }

            $avis = AvisClient::create([
                'produit_id' => $request->produit_id,
                'utilisateur_id' => $request->user()->id,
                'note' => $request->note,
                'titre' => $request->titre,
                'corps' => $request->corps,
                'publie' => false, // À valider par admin d'abord
                'date_creation' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $avis->load('utilisateur'),
                'message' => 'Votre avis a été soumis et sera publié après validation'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/avis/{id}",
     *     summary="Modifier mon avis",
     *     tags={"Avis Clients"},
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
     *             @OA\Property(property="note", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="titre", type="string"),
     *             @OA\Property(property="corps", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Avis modifié")
     * )
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'note' => 'sometimes|required|integer|min:1|max:5',
            'titre' => 'nullable|string|max:190',
            'corps' => 'nullable|string|max:5000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $avis = AvisClient::where('id', $id)
                ->where('utilisateur_id', $request->user()->id)
                ->firstOrFail();

            // Si déjà publié, on ne peut plus modifier
            if ($avis->publie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet avis est déjà publié et ne peut plus être modifié'
                ], 400);
            }

            $avis->update($request->only(['note', 'titre', 'corps']));

            return response()->json([
                'success' => true,
                'data' => $avis,
                'message' => 'Avis modifié avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Avis non trouvé ou non autorisé'
            ], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/avis/{id}",
     *     summary="Supprimer mon avis",
     *     tags={"Avis Clients"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Avis supprimé")
     * )
     */
    public function destroy(Request $request, $id)
    {
        try {
            $avis = AvisClient::where('id', $id)
                ->where('utilisateur_id', $request->user()->id)
                ->firstOrFail();

            $avis->delete();

            return response()->json([
                'success' => true,
                'message' => 'Avis supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Avis non trouvé ou non autorisé'
            ], 404);
        }
    }

    /**
     * ========== ADMIN ROUTES ==========
     */

    /**
     * @OA\Get(
     *     path="/api/admin/avis",
     *     summary="Lister tous les avis (Admin)",
     *     tags={"Avis Clients - Admin"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Liste de tous les avis")
     * )
     */
    public function adminList(Request $request)
    {
        try {
            $avis = AvisClient::with(['utilisateur', 'produit'])
                ->orderBy('date_creation', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $avis
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
     *     path="/api/admin/avis/{id}/publier",
     *     summary="Publier ou dépublier un avis (Admin)",
     *     tags={"Avis Clients - Admin"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             required={"publie"},
     *             @OA\Property(property="publie", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Statut modifié")
     * )
     */
    public function togglePublish(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'publie' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $avis = AvisClient::findOrFail($id);
            $avis->publie = $request->publie;
            $avis->save();

            return response()->json([
                'success' => true,
                'data' => $avis,
                'message' => $request->publie ? 'Avis publié' : 'Avis dépublié'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Avis non trouvé'
            ], 404);
        }
    }
}
