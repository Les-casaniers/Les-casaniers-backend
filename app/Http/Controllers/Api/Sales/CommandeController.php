<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\CommandeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Commandes",
 *     description="Gestion des commandes clients"
 * )
 */
class CommandeController extends Controller
{
    public function __construct(
        private readonly CommandeService $commandeService
    ) {
    }

    /**
     * @OA\Get(
     *     path="/commandes",
     *     summary="Lister les commandes",
     *     tags={"Commandes"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="statut", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Liste commandes")
     * )
     */
    public function index(Request $request)
    {
        $data = $this->commandeService->index((int) $request->user()->id, $request->query('statut'));

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    /**
     * @OA\Get(
     *     path="/commandes/{uuid}",
     *     summary="Détail d'une commande",
     *     tags={"Commandes"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Détail commande")
     * )
     */
    public function show(Request $request, string $uuid)
    {
        try {
            $data = $this->commandeService->show((int) $request->user()->id, $uuid);
            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/commandes",
     *     summary="Créer une commande depuis le panier actif",
     *     tags={"Commandes"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="livraison", type="number", format="float", example=15000),
     *             @OA\Property(property="devise", type="string", example="MGA"),
     *             @OA\Property(property="adresse_expedition_id", type="integer", nullable=true, example=null),
     *             @OA\Property(property="adresse_facturation_id", type="integer", nullable=true, example=null),
     *             @OA\Property(property="meta_json", type="object")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Commande créée")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'livraison' => ['nullable', 'numeric', 'min:0'],
                'devise' => ['nullable', 'string', 'size:3'],
                'adresse_expedition_id' => ['nullable', 'integer'],
                'adresse_facturation_id' => ['nullable', 'integer'],
                'meta_json' => ['nullable', 'array'],
            ]);

            $data = $this->commandeService->createFromPanier((int) $request->user()->id, $validated);

            return response()->json(['success' => true, 'data' => $data], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/commandes/{uuid}/statut",
     *     summary="Changer le statut d'une commande",
     *     tags={"Commandes"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"statut"},
     *             @OA\Property(property="statut", type="string", example="payee")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Statut modifié")
     * )
     */
    public function updateStatus(Request $request, string $uuid)
    {
        try {
            $validated = $request->validate([
                'statut' => ['required', 'string', 'in:en_attente,payee,en_traitement,expediee,terminee,annulee,remboursee'],
            ]);

            $data = $this->commandeService->updateStatus((int) $request->user()->id, $uuid, $validated['statut']);

            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/commandes/{uuid}/cancel",
     *     summary="Annuler une commande",
     *     tags={"Commandes"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Commande annulée")
     * )
     */
    public function cancel(Request $request, string $uuid)
    {
        try {
            $data = $this->commandeService->cancel((int) $request->user()->id, $uuid);
            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }
}
