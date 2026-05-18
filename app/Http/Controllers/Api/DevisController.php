<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\DevisService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class DevisController extends Controller
{
    protected $devisService;

    public function __construct(DevisService $devisService)
    {
        $this->devisService = $devisService;
    }

    /**
     * Récupérer les devis de l'utilisateur connecté
     */
    public function index(Request $request)
    {
        try {
            $devis = $this->devisService->index((int) $request->user()->id);
            
            return response()->json([
                'success' => true,
                'data' => $devis
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des devis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau devis
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'utilisateur_id' => 'required|exists:utilisateurs,id',
                'panier_id' => 'nullable|exists:paniers,id',
                'note' => 'nullable|string',
                'devise' => 'nullable|string|size:3|in:MGA,EUR,USD'
            ]);
            
            $devis = $this->devisService->create(
                (int) $validated['utilisateur_id'],
                [
                    'panier_id' => $validated['panier_id'] ?? null,
                    'note' => $validated['note'] ?? null,
                    'devise' => $validated['devise'] ?? 'MGA'
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Devis créé avec succès',
                'data' => [
                    'id' => $devis->id,
                    'devis_id' => $devis->id,
                    'utilisateur_id' => $devis->utilisateur_id,
                    'panier_id' => $devis->panier_id,
                    'statut' => $devis->statut,
                    'note' => $devis->note,
                    'montant_total' => $devis->montant_total,
                    'devise' => $devis->devise,
                    'date_creation' => $devis->date_creation
                ]
            ], 201);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
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
     * Récupérer un devis spécifique
     */
    public function show(Request $request, $id)
    {
        try {
            $devis = $this->devisService->show((int) $request->user()->id, (int) $id);
            
            return response()->json([
                'success' => true,
                'data' => $devis
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Mettre à jour un devis
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'note' => 'nullable|string',
                'panier_id' => 'nullable|exists:paniers,id'
            ]);
            
            $devis = $this->devisService->update(
                (int) $request->user()->id,
                (int) $id,
                $validated
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Devis mis à jour avec succès',
                'data' => $devis
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Envoyer un devis
     */
    public function envoyer(Request $request, $id)
    {
        try {
            $devis = $this->devisService->send((int) $request->user()->id, (int) $id);
            
            return response()->json([
                'success' => true,
                'message' => 'Devis envoyé avec succès',
                'data' => $devis
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Accepter un devis et créer la commande
     */
    public function accepter(Request $request, $id)
    {
        try {
            $result = $this->devisService->accept((int) $request->user()->id, (int) $id);
            
            return response()->json([
                'success' => true,
                'message' => 'Devis accepté et commande créée',
                'data' => $result
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Refuser un devis
     */
    public function refuser(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'note' => 'nullable|string'
            ]);
            
            $devis = $this->devisService->refuse(
                (int) $request->user()->id,
                (int) $id,
                $validated['note'] ?? null
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Devis refusé',
                'data' => $devis
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Expirer un devis
     */
    public function expirer(Request $request, $id)
    {
        try {
            $devis = $this->devisService->expire((int) $request->user()->id, (int) $id);
            
            return response()->json([
                'success' => true,
                'message' => 'Devis expiré',
                'data' => $devis
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Supprimer un devis
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->devisService->delete((int) $request->user()->id, (int) $id);
            
            return response()->json([
                'success' => true,
                'message' => 'Devis supprimé avec succès'
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Admin - Liste tous les devis
     */
    public function adminIndex(Request $request)
    {
        try {
            $devis = $this->devisService->adminIndex($request->query('statut'));
            
            return response()->json([
                'success' => true,
                'data' => $devis
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des devis'
            ], 500);
        }
    }

    /**
     * Admin - Créer un devis pour un utilisateur
     */
    public function adminStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'utilisateur_id' => 'required|exists:utilisateurs,id',
                'panier_id' => 'nullable|exists:paniers,id',
                'note' => 'nullable|string',
                'devise' => 'nullable|string|size:3|in:MGA,EUR,USD'
            ]);
            
            $devis = $this->devisService->create(
                (int) $validated['utilisateur_id'],
                [
                    'panier_id' => $validated['panier_id'] ?? null,
                    'note' => $validated['note'] ?? null,
                    'devise' => $validated['devise'] ?? 'MGA'
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Devis créé avec succès',
                'data' => $devis
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }
}