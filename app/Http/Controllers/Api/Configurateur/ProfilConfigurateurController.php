<?php

namespace App\Http\Controllers\Api\Configurateur;

use App\Http\Controllers\Controller;
use App\Models\ProfilConfigurateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Profils Configurateur",
 *     description="Templates pour configurer des PC sur mesure"
 * )
 */
class ProfilConfigurateurController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/profils-configurateur",
     *     summary="Liste des profils disponibles",
     *     tags={"Profils Configurateur"},
     *     @OA\Response(response=200, description="Liste des profils")
     * )
     */
    public function index()
    {
        try {
            // Correction : enlever 'prix_min' qui n'existe pas
            $profils = ProfilConfigurateur::where('actif', true)
                ->orderBy('id', 'asc')  // ← Changé : tri par id au lieu de prix_min
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $profils,
                'message' => 'Liste des profils récupérée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/profils-configurateur/{slug}",
     *     summary="Détails d'un profil",
     *     tags={"Profils Configurateur"},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Détails du profil"),
     *     @OA\Response(response=404, description="Profil non trouvé")
     * )
     */
    public function show($slug)
    {
        try {
            // Correction : utiliser find() au lieu de firstOrFail() pour éviter l'erreur 500
            $profil = ProfilConfigurateur::where('slug', $slug)
                ->where('actif', true)
                ->first();
            
            if (!$profil) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil non trouvé'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $profil,
                'message' => 'Détails du profil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Post(
     *     path="/api/admin/profils-configurateur",
     *     summary="Créer un profil (Admin)",
     *     tags={"Profils Configurateur - Admin"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom", "emplacements"},
     *             @OA\Property(property="nom", type="string", example="Gaming Haut de gamme"),
     *             @OA\Property(property="description", type="string", example="Config pour gaming 4K ultra"),
     *             @OA\Property(property="emplacements", type="array", @OA\Items(type="string"), example={"processeur","carte_mere","ram","stockage","carte_graphique"})
     *         )
     *     ),
     *     @OA\Response(response=201, description="Profil créé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:190',
            'description' => 'nullable|string',
            'emplacements' => 'required|array|min:1'
            // Supprimé prix_min et prix_max car ils n'existent pas dans la table
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $profil = ProfilConfigurateur::create([
                'nom' => $request->nom,
                'slug' => Str::slug($request->nom),
                'description' => $request->description,
                'emplacements' => json_encode($request->emplacements), // Convertir en JSON
                'actif' => true
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $profil,
                'message' => 'Profil créé avec succès'
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}