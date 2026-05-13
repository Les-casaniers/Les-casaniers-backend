<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\DevisService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Devis",
 *     description="Gestion des devis clients"
 * )
 */
class DevisController extends Controller
{
    public function __construct(
        private readonly DevisService $devisService
    ) {
    }

    /**
     * @OA\Get(
     *     path="/devis",
     *     summary="Lister les devis",
     *     tags={"Devis"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="statut", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Liste des devis")
     * )
     */
    public function index(Request $request)
    {
        $data = $this->devisService->index((int) $request->user()->id, $request->query('statut'));

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *     path="/devis/{id}",
     *     summary="Détail d'un devis",
     *     tags={"Devis"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Détail devis")
     * )
     */
    public function show(Request $request, int $id)
    {
        try {
            $data = $this->devisService->show((int) $request->user()->id, $id);
            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/devis",
     *     summary="Créer un devis",
     *     tags={"Devis"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="panier_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="note", type="string", nullable=true, example="Merci de valider rapidement"),
     *             @OA\Property(property="devise", type="string", example="MGA")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Devis créé")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'panier_id' => ['nullable', 'integer', 'exists:paniers,id'],
                'note' => ['nullable', 'string'],
                'devise' => ['nullable', 'string', 'size:3'],
            ]);

            $data = $this->devisService->create((int) $request->user()->id, $validated);

            return response()->json(['success' => true, 'data' => $data], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/devis/{id}",
     *     summary="Modifier un devis brouillon",
     *     tags={"Devis"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Devis modifié")
     * )
     */
    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'panier_id' => ['nullable', 'integer', 'exists:paniers,id'],
                'note' => ['nullable', 'string'],
            ]);

            $data = $this->devisService->update((int) $request->user()->id, $id, $validated);

            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    /**
     * @OA\Post(path="/devis/{id}/envoyer", summary="Envoyer un devis", tags={"Devis"}, security={{"sanctum": {}}}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=200, description="Devis envoyé"))
     */
    public function envoyer(Request $request, int $id)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->devisService->send((int) $request->user()->id, $id)], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    /**
     * @OA\Post(path="/devis/{id}/accepter", summary="Accepter un devis", tags={"Devis"}, security={{"sanctum": {}}}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=200, description="Devis accepté et converti en commande"))
     */
    public function accepter(Request $request, int $id)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->devisService->accept((int) $request->user()->id, $id)], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    /**
     * @OA\Post(path="/devis/{id}/refuser", summary="Refuser un devis", tags={"Devis"}, security={{"sanctum": {}}}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=200, description="Devis refusé"))
     */
    public function refuser(Request $request, int $id)
    {
        try {
            $validated = $request->validate(['note' => ['nullable', 'string']]);
            return response()->json(['success' => true, 'data' => $this->devisService->refuse((int) $request->user()->id, $id, $validated['note'] ?? null)], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    /**
     * @OA\Post(path="/devis/{id}/expirer", summary="Expirer un devis", tags={"Devis"}, security={{"sanctum": {}}}, @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=200, description="Devis expiré"))
     */
    public function expirer(Request $request, int $id)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->devisService->expire((int) $request->user()->id, $id)], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    /**
     * @OA\Delete(
     *     path="/devis/{id}",
     *     summary="Supprimer un devis brouillon",
     *     tags={"Devis"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Devis supprimé")
     * )
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $this->devisService->delete((int) $request->user()->id, $id);
            return response()->json(['success' => true, 'message' => 'Devis supprimé'], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }
}
