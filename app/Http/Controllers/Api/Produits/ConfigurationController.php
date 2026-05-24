<?php

namespace App\Http\Controllers\Api\Produits;

use App\Http\Controllers\Controller;
use App\Services\Produits\ConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @OA\Tag(
 *     name="Configurations",
 *     description="Gestion des configurations personnalisées des produits"
 * )
 */
class ConfigurationController extends Controller
{
    public function __construct(
        private readonly ConfigurationService $configurationService
    ) {
    }

    /**
     * @OA\Get(
     *     path="/configurations",
     *     summary="Lister les configurations de l'utilisateur connecté",
     *     tags={"Configurations"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Liste récupérée"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(Request $request)
    {
        $data = $this->configurationService->index($request->only(['produit_id']));

        return response()->json([
            'success' => true,
            'message' => 'Liste des configurations récupérée avec succès.',
            'data' => $data,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/configurations",
     *     summary="Créer une configuration",
     *     tags={"Configurations"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"produit_id","nom_configuration","composants_json"},
     *             @OA\Property(property="produit_id", type="integer", example=1),
     *             @OA\Property(property="nom_configuration", type="string", example="cpu"),
     *             @OA\Property(property="nom_configuration_autre", type="string", nullable=true, example=null),
     *             @OA\Property(property="devise", type="string", example="MGA"),
     *             @OA\Property(
     *                 property="composants_json",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="nom", type="string", example="Ryzen 7 7800X3D"),
     *                     @OA\Property(property="prix", type="number", format="float", example=1250000),
     *                     @OA\Property(property="quantite", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Configuration créée"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'produit_id' => ['required', 'integer', 'exists:produits,id'],
                'configurations' => ['sometimes', 'array', 'min:1'],
                'configurations.*.nom_configuration' => ['required_with:configurations', 'string', 'in:cpu,carte_mere,gpu,ram,ssd,hdd,stockage,alimentation,boitier,refroidissement,ventilateur,ecran,clavier,souris,os,reseau,autre'],
                'configurations.*.type' => ['nullable', 'string', 'max:190'],
                'configurations.*.detail' => ['nullable', 'string'],
                'configurations.*.capacite' => ['nullable', 'string', 'max:190'],
                'configurations.*.prix_total' => ['nullable', 'numeric', 'min:0'],
                'nom_configuration' => ['required_without:configurations', 'string', 'in:cpu,carte_mere,gpu,ram,ssd,hdd,stockage,alimentation,boitier,refroidissement,ventilateur,ecran,clavier,souris,os,reseau,autre'],
                'type' => ['nullable', 'string', 'max:190'],
                'detail' => ['nullable', 'string'],
                'capacite' => ['nullable', 'string', 'max:190'],
                'prix_total' => ['nullable', 'numeric', 'min:0'],
            ]);

            $configuration = $this->configurationService->store($validated);

            return response()->json([
                'success' => true,
                'message' => 'Configuration créée avec succès.',
                'data' => $configuration,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/configurations/{id}",
     *     summary="Modifier une configuration",
     *     tags={"Configurations"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nom_configuration", type="string", example="gpu"),
     *             @OA\Property(property="nom_configuration_autre", type="string", nullable=true, example=null),
     *             @OA\Property(property="devise", type="string", example="MGA"),
     *             @OA\Property(property="composants_json", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Configuration mise à jour"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'nom_configuration' => ['sometimes', 'required', 'string', 'in:cpu,carte_mere,gpu,ram,ssd,hdd,stockage,alimentation,boitier,refroidissement,ventilateur,ecran,clavier,souris,os,reseau,autre'],
                'type' => ['nullable', 'string', 'max:190'],
                'detail' => ['nullable', 'string'],
                'capacite' => ['nullable', 'string', 'max:190'],
                'prix_total' => ['nullable', 'numeric', 'min:0'],
            ]);

            $configuration = $this->configurationService->update($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Configuration mise à jour avec succès.',
                'data' => $configuration,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/configurations/{id}",
     *     summary="Supprimer une configuration",
     *     tags={"Configurations"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Configuration supprimée"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $this->configurationService->destroy($id);

            return response()->json([
                'success' => true,
                'message' => 'Configuration supprimée avec succès.',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }
}
