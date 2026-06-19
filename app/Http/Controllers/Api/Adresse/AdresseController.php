<?php

namespace App\Http\Controllers\Api\Adresse;

use App\Http\Controllers\Controller;
use App\Models\AdresseUtilisateur;
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
        try {
            $adresses = $this->adresseService->list((int) $request->user()->id);

            return response()->json([
                'success' => true,
                'data' => $adresses,
                'message' => 'Adresses récupérées avec succès',
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des adresses',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/adresses",
     *     summary="Ajouter une adresse",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom_complet","adresse_ligne1","ville","pays"},
     *             @OA\Property(property="etiquette", type="string", example="Maison"),
     *             @OA\Property(property="nom_complet", type="string", example="Jean Dupont"),
     *             @OA\Property(property="telephone", type="string", example="0341234567"),
     *             @OA\Property(property="adresse_ligne1", type="string", example="123 Rue de la Liberté"),
     *             @OA\Property(property="adresse_ligne2", type="string", example="Appartement 5", nullable=true),
     *             @OA\Property(property="ville", type="string", example="Antananarivo"),
     *             @OA\Property(property="region", type="string", example="Analamanga"),
     *             @OA\Property(property="code_postal", type="string", example="101"),
     *             @OA\Property(property="pays", type="string", example="Madagascar"),
     *             @OA\Property(property="par_defaut_expedition", type="boolean", example=false),
     *             @OA\Property(property="par_defaut_facturation", type="boolean", example=false),
     *             @OA\Property(property="image_adress", type="string", example="http://localhost/image-lieu/photo.jpg", nullable=true),
     *             @OA\Property(property="latitude", type="number", format="float", example=-18.8792),
     *             @OA\Property(property="longitude", type="number", format="float", example=47.5079)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Adresse créée avec succès"),
     *     @OA\Response(response=422, description="Erreur de validation")
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
                'image_adress' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $adresse = $this->adresseService->create((int) $request->user()->id, $validated);

            return response()->json([
                'success' => true,
                'data' => $adresse,
                'message' => 'Adresse ajoutée avec succès',
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Erreur de validation des données',
            ], 422);
        } catch (Throwable $e) {
            // ✅ Log de l'erreur pour le débogage
            \Log::error('Erreur lors de la création d\'adresse:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'adresse',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/adresses/{id}",
     *     summary="Voir une adresse",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détail de l'adresse"),
     *     @OA\Response(response=404, description="Adresse non trouvée")
     * )
     */
    public function show(Request $request, int $id)
    {
        try {
            $adresse = $this->adresseService->show($id, (int) $request->user()->id);

            return response()->json([
                'success' => true,
                'data' => $adresse,
                'message' => 'Adresse récupérée avec succès',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Adresse non trouvée',
            ], 404);
        } catch (Throwable $e) {
            \Log::error('Erreur lors de la récupération de l\'adresse:', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'adresse',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/adresses/{id}",
     *     summary="Modifier une adresse",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="etiquette", type="string", example="Maison"),
     *             @OA\Property(property="nom_complet", type="string", example="Jean Dupont"),
     *             @OA\Property(property="telephone", type="string", example="0341234567"),
     *             @OA\Property(property="adresse_ligne1", type="string", example="123 Rue de la Liberté"),
     *             @OA\Property(property="adresse_ligne2", type="string", example="Appartement 5", nullable=true),
     *             @OA\Property(property="ville", type="string", example="Antananarivo"),
     *             @OA\Property(property="region", type="string", example="Analamanga"),
     *             @OA\Property(property="code_postal", type="string", example="101"),
     *             @OA\Property(property="pays", type="string", example="Madagascar"),
     *             @OA\Property(property="par_defaut_expedition", type="boolean"),
     *             @OA\Property(property="par_defaut_facturation", type="boolean"),
     *             @OA\Property(property="image_adress", type="string", nullable=true),
     *             @OA\Property(property="latitude", type="number", format="float", nullable=true),
     *             @OA\Property(property="longitude", type="number", format="float", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Adresse modifiée avec succès"),
     *     @OA\Response(response=404, description="Adresse non trouvée"),
     *     @OA\Response(response=422, description="Erreur de validation")
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
                'image_adress' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $adresse = $this->adresseService->update($id, (int) $request->user()->id, $validated);

            return response()->json([
                'success' => true,
                'data' => $adresse,
                'message' => 'Adresse modifiée avec succès',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Erreur de validation des données',
            ], 422);
        } catch (Throwable $e) {
            \Log::error('Erreur lors de la modification de l\'adresse:', [
                'id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de l\'adresse',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/adresses/{id}",
     *     summary="Supprimer une adresse",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Adresse supprimée avec succès"),
     *     @OA\Response(response=404, description="Adresse non trouvée")
     * )
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $this->adresseService->delete($id, (int) $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Adresse supprimée avec succès',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Adresse non trouvée',
            ], 404);
        } catch (Throwable $e) {
            \Log::error('Erreur lors de la suppression de l\'adresse:', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'adresse',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/adresses/{id}/defaut-expedition",
     *     summary="Définir une adresse par défaut pour l'expédition",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Adresse par défaut définie"),
     *     @OA\Response(response=404, description="Adresse non trouvée")
     * )
     */
    public function setDefaultExpedition(Request $request, int $id)
    {
        try {
            $adresse = $this->adresseService->setDefaultExpedition($id, (int) $request->user()->id);

            return response()->json([
                'success' => true,
                'data' => $adresse,
                'message' => 'Adresse d\'expédition par défaut définie avec succès',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Adresse non trouvée',
            ], 404);
        } catch (Throwable $e) {
            \Log::error('Erreur lors de la définition de l\'adresse par défaut:', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la définition de l\'adresse par défaut',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/adresses/defaut-expedition",
     *     summary="Obtenir l'adresse par défaut pour l'expédition",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Adresse par défaut")
     * )
     */
    public function getDefaultExpedition(Request $request)
    {
        try {
            $adresse = $this->adresseService->getDefaultExpedition((int) $request->user()->id);

            return response()->json([
                'success' => true,
                'data' => $adresse,
                'message' => 'Adresse d\'expédition par défaut récupérée avec succès',
            ], 200);

        } catch (Throwable $e) {
            \Log::error('Erreur lors de la récupération de l\'adresse par défaut:', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'adresse par défaut',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/admin/adresses/utilisateur/{utilisateurId}",
     *     summary="Admin - Lister les adresses d'un utilisateur",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="utilisateurId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Liste des adresses de l'utilisateur"),
     *     @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     */
    public function adminIndexByUser(int $utilisateurId)
    {
        try {
            if (!AdresseUtilisateur::where('utilisateur_id', $utilisateurId)->exists()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['utilisateur_id' => ['Client introuvable.']],
                ], 404);
            }

            $adresses = $this->adresseService->list($utilisateurId);

            return response()->json([
                'success' => true,
                'data' => $adresses,
                'message' => 'Adresses client récupérées avec succès',
            ], 200);

        } catch (Throwable $e) {
            \Log::error('Erreur lors de la récupération des adresses client:', [
                'utilisateur_id' => $utilisateurId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des adresses',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/adresses/utilisateur/{utilisateurId}",
     *     summary="Admin - Ajouter une adresse à un utilisateur",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="utilisateurId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom_complet","adresse_ligne1","ville","pays"},
     *             @OA\Property(property="etiquette", type="string", example="Maison"),
     *             @OA\Property(property="nom_complet", type="string", example="Jean Dupont"),
     *             @OA\Property(property="telephone", type="string", example="0341234567"),
     *             @OA\Property(property="adresse_ligne1", type="string", example="123 Rue de la Liberté"),
     *             @OA\Property(property="adresse_ligne2", type="string", example="Appartement 5", nullable=true),
     *             @OA\Property(property="ville", type="string", example="Antananarivo"),
     *             @OA\Property(property="region", type="string", example="Analamanga"),
     *             @OA\Property(property="code_postal", type="string", example="101"),
     *             @OA\Property(property="pays", type="string", example="Madagascar"),
     *             @OA\Property(property="par_defaut_expedition", type="boolean", example=false),
     *             @OA\Property(property="par_defaut_facturation", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Adresse créée avec succès"),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function adminStoreForUser(Request $request, int $utilisateurId)
    {
        try {
            if (!AdresseUtilisateur::where('utilisateur_id', $utilisateurId)->exists()) {
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
                'image_adress' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $adresse = $this->adresseService->create($utilisateurId, $validated);

            return response()->json([
                'success' => true,
                'data' => $adresse,
                'message' => 'Adresse client ajoutée avec succès',
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Erreur de validation des données',
            ], 422);
        } catch (Throwable $e) {
            \Log::error('Erreur lors de la création d\'adresse client:', [
                'utilisateur_id' => $utilisateurId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'adresse',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/admin/adresses/utilisateur/{utilisateurId}/{id}",
     *     summary="Admin - Modifier une adresse d'un utilisateur",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="utilisateurId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Adresse modifiée avec succès"),
     *     @OA\Response(response=404, description="Utilisateur ou adresse non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function adminUpdateForUser(Request $request, int $utilisateurId, int $id)
    {
        try {
            if (!AdresseUtilisateur::where('utilisateur_id', $utilisateurId)->exists()) {
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
                'image_adress' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $adresse = $this->adresseService->update($id, $utilisateurId, $validated);

            return response()->json([
                'success' => true,
                'data' => $adresse,
                'message' => 'Adresse client modifiée avec succès',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Erreur de validation des données',
            ], 422);
        } catch (Throwable $e) {
            \Log::error('Erreur lors de la modification de l\'adresse client:', [
                'utilisateur_id' => $utilisateurId,
                'id' => $id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de l\'adresse',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/admin/adresses/utilisateur/{utilisateurId}/{id}",
     *     summary="Admin - Supprimer une adresse d'un utilisateur",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="utilisateurId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Adresse supprimée avec succès"),
     *     @OA\Response(response=404, description="Utilisateur ou adresse non trouvé")
     * )
     */
    public function adminDestroyForUser(int $utilisateurId, int $id)
    {
        try {
            if (!AdresseUtilisateur::where('utilisateur_id', $utilisateurId)->exists()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['utilisateur_id' => ['Client introuvable.']],
                ], 404);
            }

            $this->adresseService->delete($id, $utilisateurId);

            return response()->json([
                'success' => true,
                'message' => 'Adresse client supprimée avec succès',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Adresse non trouvée',
            ], 404);
        } catch (Throwable $e) {
            \Log::error('Erreur lors de la suppression de l\'adresse client:', [
                'utilisateur_id' => $utilisateurId,
                'id' => $id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'adresse',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/adresses/upload-image",
     *     summary="Uploader une image pour une adresse",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary",
     *                     description="Image à uploader (JPG, PNG, GIF, WEBP - max 2MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Image uploadée avec succès"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function uploadImage(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
            ]);

            $imageUrl = $this->adresseService->uploadImage($request->file('image'));

            return response()->json([
                'success' => true,
                'image_url' => $imageUrl,
                'message' => 'Image téléchargée avec succès'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Erreur de validation de l\'image'
            ], 422);
        } catch (Throwable $e) {
            \Log::error('Erreur lors de l\'upload de l\'image:', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload de l\'image',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}