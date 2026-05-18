<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\DevisService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class DevisController extends Controller
{
    public function __construct(
        private readonly DevisService $devisService
    ) {
    }

    public function index(Request $request)
    {
        $data = $this->devisService->index((int) $request->user()->id, $request->query('statut'));

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    public function show(Request $request, int $id)
    {
        try {
            $data = $this->devisService->show((int) $request->user()->id, $id);
            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'panier_id' => ['nullable', 'integer', 'exists:paniers,id'],
                'note' => ['nullable', 'string'],
                'devise' => ['nullable', 'string', 'size:3'],
                'devis_id' => ['nullable', 'integer', 'exists:devis,id'],
            ]);

            $data = $this->devisService->create((int) $request->user()->id, $validated);

            return response()->json(['success' => true, 'data' => $data], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

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

    public function envoyer(Request $request, int $id)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->devisService->send((int) $request->user()->id, $id)], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function accepter(Request $request, int $id)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->devisService->accept((int) $request->user()->id, $id)], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function refuser(Request $request, int $id)
    {
        try {
            $validated = $request->validate(['note' => ['nullable', 'string']]);
            return response()->json(['success' => true, 'data' => $this->devisService->refuse((int) $request->user()->id, $id, $validated['note'] ?? null)], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function expirer(Request $request, int $id)
    {
        try {
            return response()->json(['success' => true, 'data' => $this->devisService->expire((int) $request->user()->id, $id)], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function destroy(Request $request, int $id)
    {
        try {
            $this->devisService->delete((int) $request->user()->id, $id);
            return response()->json(['success' => true, 'message' => 'Devis supprime'], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function adminIndex(Request $request)
    {
        $data = $this->devisService->adminIndex($request->query('statut'));

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    public function adminStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'utilisateur_id' => ['required', 'integer', 'exists:utilisateurs,id'],
                'panier_id' => ['nullable', 'integer', 'exists:paniers,id'],
                'note' => ['nullable', 'string'],
                'devise' => ['nullable', 'string', 'size:3'],
            ]);

            $data = $this->devisService->create((int) $validated['utilisateur_id'], $validated);

            return response()->json(['success' => true, 'data' => $data], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }
}