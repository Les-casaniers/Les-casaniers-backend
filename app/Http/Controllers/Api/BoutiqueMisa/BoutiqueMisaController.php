<?php
// app/Http/Api/BoutiqueMisa/BoutiqueMisaController.php

// namespace App\Http\Controllers\Api\BoutiqueMisa;

// use App\Http\Controllers\Controller;
// use App\Services\Boutique\BoutiqueMisaService;
// use Illuminate\Http\Request;
// use Illuminate\Http\JsonResponse;

// class BoutiqueMisaController extends Controller
// {
//     protected BoutiqueMisaService $boutiqueMisaService;

//     public function __construct(BoutiqueMisaService $boutiqueMisaService)
//     {
//         $this->boutiqueMisaService = $boutiqueMisaService;
//     }

//     public function index(Request $request): JsonResponse
//     {
//         $filters = $request->validate([
//             'search' => 'nullable|string|max:255',
//             'stock_min' => 'nullable|integer|min:0',
//             'prix_max' => 'nullable|numeric|min:0',
//             'per_page' => 'nullable|integer|min:1|max:100',
//         ]);

//         $items = $this->boutiqueMisaService->getAll($filters);
//         return response()->json($items);
//     }

//     public function show(int $id): JsonResponse
//     {
//         $item = $this->boutiqueMisaService->findById($id);
//         if (!$item) {
//             return response()->json(['message' => 'Article non trouvé'], 404);
//         }
        
//         return response()->json($item);
//     }

//     public function store(Request $request): JsonResponse
//     {
//         $validated = $request->validate([
//             'nom' => 'required|string|max:255',
//             'description' => 'nullable|string',
//             'stock' => 'required|integer|min:0',
//             'prix' => 'required|numeric|min:0',
//             'image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
//         ]);

//         $item = $this->boutiqueMisaService->create($validated);
        
//         return response()->json($item, 201);
//     }

//     public function update(Request $request, int $id): JsonResponse
//     {
//         $validated = $request->validate([
//             'nom' => 'sometimes|required|string|max:255',
//             'description' => 'nullable|string',
//             'stock' => 'sometimes|required|integer|min:0',
//             'prix' => 'sometimes|required|numeric|min:0',
//             'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
//         ]);

//         $item = $this->boutiqueMisaService->update($id, $validated);
//         if (!$item) {
//             return response()->json(['message' => 'Article non trouvé'], 404);
//         }
        
//         return response()->json($item);
//     }

//     public function destroy(int $id): JsonResponse
//     {
//         $deleted = $this->boutiqueMisaService->delete($id);
//         if (!$deleted) {
//             return response()->json(['message' => 'Article non trouvé'], 404);
//         }
//         return response()->json(['message' => 'Article supprimé avec succès']);
//     }

//     public function updateStock(Request $request, int $id): JsonResponse
//     {
//         $validated = $request->validate([
//             'stock' => 'required|integer|min:0',
//         ]);

//         $item = $this->boutiqueMisaService->updateStock($id, $validated['stock']);
//         if (!$item) {
//             return response()->json(['message' => 'Article non trouvé'], 404);
//         }
        
//         return response()->json($item);
//     }
// }

namespace App\Http\Controllers\Api\BoutiqueMisa;

use App\Http\Controllers\Controller;
use App\Services\Boutique\BoutiqueMisaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BoutiqueMisaController extends Controller
{
    protected BoutiqueMisaService $boutiqueMisaService;

    public function __construct(BoutiqueMisaService $boutiqueMisaService)
    {
        $this->boutiqueMisaService = $boutiqueMisaService;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'stock_min' => 'nullable|integer|min:0',
            'prix_max' => 'nullable|numeric|min:0',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $items = $this->boutiqueMisaService->getAll($filters);
        return response()->json($items);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->boutiqueMisaService->findById($id);
        if (!$item) {
            return response()->json(['message' => 'Article non trouvé'], 404);
        }
        
        return response()->json($item);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'stock' => 'required|integer|min:0',
            'prix' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $item = $this->boutiqueMisaService->create($validated);
        
        return response()->json($item, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'stock' => 'sometimes|required|integer|min:0',
            'prix' => 'sometimes|required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $item = $this->boutiqueMisaService->update($id, $validated);
        if (!$item) {
            return response()->json(['message' => 'Article non trouvé'], 404);
        }
        
        return response()->json($item);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->boutiqueMisaService->delete($id);
        if (!$deleted) {
            return response()->json(['message' => 'Article non trouvé'], 404);
        }
        return response()->json(['message' => 'Article supprimé avec succès']);
    }

    public function updateStock(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $item = $this->boutiqueMisaService->updateStock($id, $validated['stock']);
        if (!$item) {
            return response()->json(['message' => 'Article non trouvé'], 404);
        }
        
        return response()->json($item);
    }
}