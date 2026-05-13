<?php

namespace App\Http\Controllers\Api\AvisClients;

use App\Http\Controllers\Controller;
use App\Services\AvisClients\AvisClientService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Avis Clients",
 *     description="Gestion des avis clients et modération"
 * )
 */
class AvisClientController extends Controller
{
    public function __construct(
        private readonly AvisClientService $avisClientService
    ) {
    }

    /**
     * @OA\Get(path="/produits/{produitId}/avis", summary="Lister les avis publiés d'un produit", tags={"Avis Clients"}, @OA\Parameter(name="produitId", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=200, description="Liste avis publiés"))
     */
    public function getAvisByProduit(int $produitId)
    {
        return response()->json([
            'success' => true,
            'data' => $this->avisClientService->getAvisByProduit($produitId),
        ], 200);
    }

    /**
     * @OA\Get(path="/avis/{id}", summary="Détail d'un avis", tags={"Avis Clients"}, security={{"sanctum": {}}}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=200, description="Détail avis"))
     */
    public function show(int $id)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->avisClientService->getAvisById($id),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    /**
     * @OA\Get(path="/mes-avis", summary="Mes avis", tags={"Avis Clients"}, security={{"sanctum": {}}}, @OA\Response(response=200, description="Mes avis"))
     */
    public function getMesAvis(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->avisClientService->getMesAvis((int) $request->user()->id),
        ], 200);
    }

    /**
     * @OA\Post(path="/avis", summary="Ajouter un avis", tags={"Avis Clients"}, security={{"sanctum": {}}}, @OA\RequestBody(required=true, @OA\JsonContent(required={"produit_id","note"}, @OA\Property(property="produit_id", type="integer", example=1), @OA\Property(property="note", type="integer", example=5), @OA\Property(property="corps", type="string", example="Très bon produit"))), @OA\Response(response=201, description="Avis créé"))
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'produit_id' => ['required', 'integer', 'exists:produits,id'],
                'note' => ['required', 'integer', 'min:1', 'max:5'],
                'corps' => ['nullable', 'string'],
            ]);

            $avis = $this->avisClientService->ajouterAvis((int) $request->user()->id, $validated);

            return response()->json(['success' => true, 'data' => $avis], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * @OA\Put(path="/avis/{id}", summary="Modifier un avis", tags={"Avis Clients"}, security={{"sanctum": {}}}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=200, description="Avis modifié"))
     */
    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'note' => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
                'corps' => ['nullable', 'string'],
                'publie' => ['nullable', 'boolean'],
            ]);

            $isAdmin = method_exists($request->user(), 'isAdmin') ? (bool) $request->user()->isAdmin() : false;
            $avis = $this->avisClientService->modifierAvis($id, $validated, (int) $request->user()->id, $isAdmin);

            return response()->json(['success' => true, 'data' => $avis], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    /**
     * @OA\Delete(path="/avis/{id}", summary="Supprimer un avis", tags={"Avis Clients"}, security={{"sanctum": {}}}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=200, description="Avis supprimé"))
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $isAdmin = method_exists($request->user(), 'isAdmin') ? (bool) $request->user()->isAdmin() : false;
            $this->avisClientService->supprimerAvis($id, (int) $request->user()->id, $isAdmin);
            return response()->json(['success' => true, 'message' => 'Avis supprimé'], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function adminList(Request $request)
    {
        $filters = $request->only(['publie', 'produit_id', 'note', 'keyword']);
        return response()->json([
            'success' => true,
            'data' => $this->avisClientService->getAvisAdmin($filters),
        ], 200);
    }

    public function togglePublish(Request $request, int $id)
    {
        $validated = $request->validate(['publie' => ['required', 'boolean']]);
        $data = $validated['publie']
            ? $this->avisClientService->publierAvis($id)
            : $this->avisClientService->refuserAvis($id);

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    public function getStatistiquesProduit(int $produitId)
    {
        return response()->json([
            'success' => true,
            'data' => $this->avisClientService->getStatistiquesProduit($produitId),
        ], 200);
    }

    public function search(Request $request)
    {
        $isAdmin = method_exists($request->user(), 'isAdmin') ? (bool) $request->user()->isAdmin() : false;
        return response()->json([
            'success' => true,
            'data' => $this->avisClientService->searchAvis($request->only(['produit_id', 'note', 'keyword', 'publie']), $isAdmin),
        ], 200);
    }

    public function latest(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        return response()->json([
            'success' => true,
            'data' => $this->avisClientService->getLatestAvis($limit),
        ], 200);
    }
}
