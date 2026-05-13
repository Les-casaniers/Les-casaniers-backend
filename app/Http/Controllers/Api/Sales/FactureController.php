<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\FactureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class FactureController extends Controller
{
    public function __construct(
        private readonly FactureService $factureService
    ) {
    }

    public function index(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->factureService->index((int) $request->user()->id),
        ], 200);
    }

    public function show(Request $request, int $id)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->factureService->show((int) $request->user()->id, $id),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function download(Request $request, int $id)
    {
        try {
            $path = $this->factureService->documentPathForUser($id, (int) $request->user()->id);

            return Storage::disk('local')->download($path);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function adminIndex()
    {
        return response()->json([
            'success' => true,
            'data' => $this->factureService->adminIndex(),
        ], 200);
    }

    public function adminShow(int $id)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->factureService->adminShow($id),
            ], 200);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Facture introuvable'], 404);
        }
    }

    public function adminStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'commande_uuid' => ['required', 'string', 'exists:commandes,commande_uuid'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $this->factureService->createFromCommande($validated['commande_uuid']),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    public function adminEmit(int $id)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->factureService->emit($id),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function adminMarkPaid(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'methode_paiement' => ['nullable', 'string', 'max:80'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $this->factureService->markPaid($id, $validated['methode_paiement'] ?? null),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function adminCancel(int $id)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->factureService->cancel($id),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function adminDownload(int $id)
    {
        try {
            $path = $this->factureService->documentPathForAdmin($id);

            return Storage::disk('local')->download($path);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }
}
