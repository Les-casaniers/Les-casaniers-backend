<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\CommandeService;
use App\Services\Sales\FactureService;
use Illuminate\Http\Request;
use App\Models\Commande;
use Illuminate\Validation\ValidationException;
use Throwable;
use Illuminate\Support\Facades\DB;
use App\Models\Produit;

/**
 * @OA\Tag(
 *     name="Commandes",
 *     description="Gestion des commandes clients"
 * )
 */
class CommandeController extends Controller
{
    public function __construct(
        private readonly CommandeService $commandeService,
        private readonly FactureService $factureService
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
                'devis_id' => ['nullable', 'integer', 'exists:devis,id'], // ← AJOUTER CECI
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
            if ($validated['statut'] === 'payee') {
                $this->factureService->createFromCommandeIfMissing($uuid);
            }

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

    public function adminIndex(Request $request)
    {
        $data = $this->commandeService->adminIndex($request->query('statut'));

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    public function adminStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'utilisateur_id' => ['required', 'integer', 'exists:utilisateurs,id'],
                'livraison' => ['nullable', 'numeric', 'min:0'],
                'devise' => ['nullable', 'string', 'size:3'],
                'adresse_expedition_id' => ['nullable', 'integer'],
                'adresse_facturation_id' => ['nullable', 'integer'],
                'meta_json' => ['nullable', 'array'],
            ]);

            $data = $this->commandeService->createFromPanier((int) $validated['utilisateur_id'], $validated);

            return response()->json(['success' => true, 'data' => $data], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    public function adminShow(string $uuid)
    {
        try {
            $data = $this->commandeService->adminShow($uuid);
            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function adminUpdateStatus(Request $request, string $uuid)
    {
        try {
            $validated = $request->validate([
                'statut' => ['required', 'string', 'in:en_attente,payee,en_traitement,expediee,terminee,annulee,remboursee'],
            ]);

            $data = $this->commandeService->adminUpdateStatus($uuid, $validated['statut']);
            if ($validated['statut'] === 'payee') {
                $this->factureService->createFromCommandeIfMissing($uuid);
            }

            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function adminCancel(string $uuid)
    {
        try {
            $data = $this->commandeService->adminCancel($uuid);
            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function getLastNumber()
    {
        try {
            $lastCommande = Commande::orderBy('id', 'desc')->first();
            
            $lastNumber = 0;
            if ($lastCommande && $lastCommande->commande_uuid) {
                // Extraire le numéro de CMD-XXX
                $parts = explode('-', $lastCommande->commande_uuid);
                $lastNumber = (int) end($parts);
            }
            
            return response()->json([
                'success' => true,
                'last_number' => $lastNumber
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => true,
                'last_number' => 0
            ]);
        }
    }

    /**
     * Admin - Rembourser une commande
     */
    /**
 * Admin - Rembourser une commande
 */
    public function adminRembourser(Request $request, string $uuid)
    {
        try {
            $commande = Commande::where('commande_uuid', $uuid)->first();
            
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }
            
            // Vérifier si la commande peut être remboursée
            if ($commande->statut !== 'payee') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seules les commandes payées peuvent être remboursées'
                ], 422);
            }
            
            DB::transaction(function () use ($commande) {
                $commande->update(['statut' => 'remboursee']);
                
                // Restaurer le stock si nécessaire
                if ($commande->produit_id) {
                    $produit = Produit::find($commande->produit_id);
                    if ($produit) {
                        $produit->increment('quantite_stock', $commande->quantite);
                    }
                }
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Commande remboursée avec succès'
            ], 200);
            
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du remboursement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin - Supprimer définitivement une commande
     */
    public function adminDestroy(Request $request, string $uuid)
    {
        try {
            $commande = Commande::where('commande_uuid', $uuid)->first();
            
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }
            
            $commande->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Commande supprimée avec succès'
            ], 200);
            
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }
}
