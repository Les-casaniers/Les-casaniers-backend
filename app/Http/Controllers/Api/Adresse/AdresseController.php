<?php

namespace App\Http\Controllers\Api\Adresse;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Services\Adresse\AdresseService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Adresses",
 *     description="Gestion des adresses utilisateurs"
 * )
 */
class AdresseController extends Controller
{
    public function __construct(
        private readonly AdresseService $adresseService
    ) {}

    /**
     * @OA\Get(
     *     path="/adresses",
     *     summary="Lister mes adresses",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Liste des adresses")
     * )
     */
    public function index(Request $request)
    {
        $adresses = $this->adresseService->list((int) $request->user()->id);

        return response()->json([
            'success' => true,
            'data' => $adresses,
            'message' => 'Adresses recuperees',
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/adresses",
     *     summary="Ajouter une adresse",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=201, description="Adresse creee")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'etiquette' => 'nullable|string|max:50',
                'nom_complet' => 'required|string|max:190',
                'telephone' => 'nullable|string|max:30',
                'adresse_ligne1' => 'required|string|max:190',
                'adresse_ligne2' => 'nullable|string|max:190',
                'ville' => 'required|string|max:120',
                'region' => 'nullable|string|max:120',
                'code_postal' => 'nullable|string|max:20',
                'pays' => 'required|string|max:80',
                'par_defaut_expedition' => 'boolean',
                'par_defaut_facturation' => 'boolean',
                // ✅ NOUVEAUX CHAMPS
                'image_adress' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $adresse = $this->adresseService->create((int) $request->user()->id, $validated);

            return response()->json([
                'success' => true,
                'data' => $adresse,
                'message' => 'Adresse ajoutee avec succes',
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/adresses/{id}",
     *     summary="Voir une adresse",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detail adresse")
     * )
     */
    public function show(Request $request, int $id)
    {
        try {
            $adresse = $this->adresseService->show($id, (int) $request->user()->id);

            return response()->json([
                'success' => true,
                'data' => $adresse,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/adresses/{id}",
     *     summary="Modifier une adresse",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Adresse modifiee")
     * )
     */
    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'etiquette' => 'nullable|string|max:50',
                'nom_complet' => 'sometimes|required|string|max:190',
                'telephone' => 'nullable|string|max:30',
                'adresse_ligne1' => 'sometimes|required|string|max:190',
                'adresse_ligne2' => 'nullable|string|max:190',
                'ville' => 'sometimes|required|string|max:120',
                'region' => 'nullable|string|max:120',
                'code_postal' => 'nullable|string|max:20',
                'pays' => 'sometimes|required|string|max:80',
                'par_defaut_expedition' => 'boolean',
                'par_defaut_facturation' => 'boolean',
                // ✅ NOUVEAUX CHAMPS
                'image_adress' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $adresse = $this->adresseService->update($id, (int) $request->user()->id, $validated);

            return response()->json([
                'success' => true,
                'data' => $adresse,
                'message' => 'Adresse modifiee avec succes',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/adresses/{id}",
     *     summary="Supprimer une adresse",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Adresse supprimee")
     * )
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $this->adresseService->delete($id, (int) $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Adresse supprimee avec succes',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    public function setDefaultExpedition(Request $request, int $id)
    {
        try {
            $adresse = $this->adresseService->setDefaultExpedition($id, (int) $request->user()->id);

            return response()->json([
                'success' => true,
                'data' => $adresse,
                'message' => 'Adresse d\'expedition par defaut definie',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getDefaultExpedition(Request $request)
    {
        $adresse = $this->adresseService->getDefaultExpedition((int) $request->user()->id);

        return response()->json([
            'success' => true,
            'data' => $adresse,
            'message' => 'Adresse d\'expedition par defaut',
        ], 200);
    }

    public function adminIndexByUser(int $utilisateurId)
    {
        try {
            if (!Utilisateur::query()->whereKey($utilisateurId)->exists()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['utilisateur_id' => ['Client introuvable.']],
                ], 404);
            }

            $adresses = $this->adresseService->list($utilisateurId);

            return response()->json([
                'success' => true,
                'data' => $adresses,
                'message' => 'Adresses client recuperees',
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    public function adminStoreForUser(Request $request, int $utilisateurId)
    {
        try {
            if (!Utilisateur::query()->whereKey($utilisateurId)->exists()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['utilisateur_id' => ['Client introuvable.']],
                ], 404);
            }

            $validated = $request->validate([
                'etiquette' => 'nullable|string|max:50',
                'nom_complet' => 'required|string|max:190',
                'telephone' => 'nullable|string|max:30',
                'adresse_ligne1' => 'required|string|max:190',
                'adresse_ligne2' => 'nullable|string|max:190',
                'ville' => 'required|string|max:120',
                'region' => 'nullable|string|max:120',
                'code_postal' => 'nullable|string|max:20',
                'pays' => 'required|string|max:80',
                'par_defaut_expedition' => 'boolean',
                'par_defaut_facturation' => 'boolean',
                // ✅ NOUVEAUX CHAMPS
                'image_adress' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $adresse = $this->adresseService->create($utilisateurId, $validated);

            return response()->json([
                'success' => true,
                'data' => $adresse,
                'message' => 'Adresse client ajoutee avec succes',
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    public function adminUpdateForUser(Request $request, int $utilisateurId, int $id)
    {
        try {
            if (!Utilisateur::query()->whereKey($utilisateurId)->exists()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['utilisateur_id' => ['Client introuvable.']],
                ], 404);
            }

            $validated = $request->validate([
                'etiquette' => 'nullable|string|max:50',
                'nom_complet' => 'sometimes|required|string|max:190',
                'telephone' => 'nullable|string|max:30',
                'adresse_ligne1' => 'sometimes|required|string|max:190',
                'adresse_ligne2' => 'nullable|string|max:190',
                'ville' => 'sometimes|required|string|max:120',
                'region' => 'nullable|string|max:120',
                'code_postal' => 'nullable|string|max:20',
                'pays' => 'sometimes|required|string|max:80',
                'par_defaut_expedition' => 'boolean',
                'par_defaut_facturation' => 'boolean',
                // ✅ NOUVEAUX CHAMPS
                'image_adress' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $adresse = $this->adresseService->update($id, $utilisateurId, $validated);

            return response()->json([
                'success' => true,
                'data' => $adresse,
                'message' => 'Adresse client modifiee avec succes',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    public function adminDestroyForUser(int $utilisateurId, int $id)
    {
        try {
            if (!Utilisateur::query()->whereKey($utilisateurId)->exists()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['utilisateur_id' => ['Client introuvable.']],
                ], 404);
            }

            $this->adresseService->delete($id, $utilisateurId);

            return response()->json([
                'success' => true,
                'message' => 'Adresse client supprimee avec succes',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * Upload d'image pour l'adresse
     */
    public function uploadImage(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
            ]);

            // Récupérer l'URL complète depuis le service
            $imageUrl = $this->adresseService->uploadImage($request->file('image'));

            return response()->json([
                'success' => true,
                'image_url' => $imageUrl,
                'message' => 'Image téléchargée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload: ' . $e->getMessage()
            ], 500);
        }
    }
}