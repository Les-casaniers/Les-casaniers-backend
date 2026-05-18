<?php

namespace App\Http\Controllers\Api\Guides;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Guides\GuideService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class GuideController extends Controller
{
    public function __construct(protected GuideService $guideService)
    {
    }

    /* ───────────── PUBLIC ENDPOINTS ───────────── */

    public function index(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->guideService->list($request->query(), false),
        ]);
    }

    public function recent(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->guideService->recent((int) $request->query('limit', 4)),
        ]);
    }

    public function popular(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->guideService->popular((int) $request->query('limit', 4)),
        ]);
    }

    public function featured(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->guideService->featured((int) $request->query('limit', 6)),
        ]);
    }

    public function byCategory(Request $request, string $categorie)
    {
        return response()->json([
            'success' => true,
            'data' => $this->guideService->byCategory($categorie, (int) $request->query('limit', 6)),
        ]);
    }

    public function categories()
    {
        return response()->json([
            'success' => true,
            'data' => $this->guideService->categories(),
        ]);
    }

    public function show(int $id)
    {
        $guide = $this->guideService->findPublic($id);

        if (!$guide) {
            return response()->json(['success' => false, 'message' => 'Guide non trouve'], 404);
        }

        return response()->json(['success' => true, 'data' => $guide]);
    }

    public function showBySlug(string $slug)
    {
        $guide = $this->guideService->findBySlug($slug);

        if (!$guide) {
            return response()->json(['success' => false, 'message' => 'Guide non trouve'], 404);
        }

        return response()->json(['success' => true, 'data' => $guide]);
    }

    /* ───────────── ADMIN ENDPOINTS ───────────── */

    public function adminIndex(Request $request)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'data' => $this->guideService->list($request->query(), true),
        ]);
    }

    public function adminShow(Request $request, int $id)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        $guide = $this->guideService->findAdmin($id);

        if (!$guide) {
            return response()->json(['success' => false, 'message' => 'Guide non trouve'], 404);
        }

        return response()->json(['success' => true, 'data' => $guide]);
    }

    public function store(Request $request)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        $this->validateImage($request);

        try {
            $guide = $this->guideService->create($request->all(), $request->file('image'));
            return response()->json(['success' => true, 'data' => $guide], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, int $id)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        $this->validateImage($request);

        try {
            $guide = $this->guideService->update($id, $request->all(), $request->file('image'));
            if (!$guide) {
                return response()->json(['success' => false, 'message' => 'Guide non trouve'], 404);
            }

            return response()->json(['success' => true, 'data' => $guide]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, int $id)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        return response()->json(['success' => $this->guideService->delete($id)]);
    }

    protected function validateImage(Request $request): void
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);
    }

    protected function ensureAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || !($user instanceof Admin)) {
            return response()->json(['success' => false, 'message' => 'Acces refuse'], 403);
        }

        return null;
    }
}
