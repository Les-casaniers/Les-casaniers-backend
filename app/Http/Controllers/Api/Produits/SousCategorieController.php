<?php

namespace App\Http\Controllers\Api\Produits;

use App\Http\Controllers\Controller;
use App\Services\SousCategorie\SousCategorieService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class SousCategorieController extends Controller
{
    protected $service;

    public function __construct(SousCategorieService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->getAll()
        ]);
    }

    public function show($id)
    {
        $sousCategorie = $this->service->getById($id);
        if (!$sousCategorie) {
            return response()->json(['success' => false, 'message' => 'Non trouvé'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sousCategorie
        ]);
    }

    public function store(Request $request)
    {
        try {
            $sousCategorie = $this->service->create($request->all());
            return response()->json(['success' => true, 'data' => $sousCategorie], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $sousCategorie = $this->service->update($id, $request->all());
            if (!$sousCategorie) {
                return response()->json(['success' => false, 'message' => 'Non trouvé'], 404);
            }
            return response()->json(['success' => true, 'data' => $sousCategorie]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $deleted = $this->service->delete($id);
        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Non trouvé'], 404);
        }
        return response()->json(['success' => true]);
    }
}
