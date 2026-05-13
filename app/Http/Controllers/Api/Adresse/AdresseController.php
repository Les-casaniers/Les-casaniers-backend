<?php

namespace App\Http\Controllers\Api\Adresse;

use App\Http\Controllers\Controller;
use App\Models\AdresseUtilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Adresses",
 *     description="Gestion des adresses utilisateurs"
 * )
 */
class AdresseController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/adresses",
     *     summary="Lister mes adresses",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Liste des adresses")
     * )
     */
    public function index(Request $request)
    {
        try {
            $adresses = AdresseUtilisateur::where('utilisateur_id', $request->user()->id)
                ->orderBy('par_defaut_expedition', 'desc')
                ->orderBy('date_creation', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $adresses,
                'message' => 'Adresses récupérées'
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
     *     path="/api/adresses",
     *     summary="Ajouter une nouvelle adresse",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom_complet", "adresse_ligne1", "ville", "pays"},
     *             @OA\Property(property="etiquette", type="string", example="Maison"),
     *             @OA\Property(property="nom_complet", type="string", example="Jean Dupont"),
     *             @OA\Property(property="telephone", type="string", example="0341234567"),
     *             @OA\Property(property="adresse_ligne1", type="string", example="Lot II J 83"),
     *             @OA\Property(property="adresse_ligne2", type="string", example="Ambohijatovo"),
     *             @OA\Property(property="ville", type="string", example="Antananarivo"),
     *             @OA\Property(property="region", type="string", example="Analamanga"),
     *             @OA\Property(property="code_postal", type="string", example="101"),
     *             @OA\Property(property="pays", type="string", example="Madagascar"),
     *             @OA\Property(property="par_defaut_expedition", type="boolean", example=false),
     *             @OA\Property(property="par_defaut_facturation", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Adresse créée")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
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
            'par_defaut_facturation' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        
        try {
            // Si cette adresse est définie comme par défaut pour l'expédition
            if ($request->par_defaut_expedition) {
                AdresseUtilisateur::where('utilisateur_id', $request->user()->id)
                    ->where('par_defaut_expedition', true)
                    ->update(['par_defaut_expedition' => false]);
            }
            
            // Si cette adresse est définie comme par défaut pour la facturation
            if ($request->par_defaut_facturation) {
                AdresseUtilisateur::where('utilisateur_id', $request->user()->id)
                    ->where('par_defaut_facturation', true)
                    ->update(['par_defaut_facturation' => false]);
            }
            
            $adresse = AdresseUtilisateur::create([
                'utilisateur_id' => $request->user()->id,
                'etiquette' => $request->etiquette,
                'nom_complet' => $request->nom_complet,
                'telephone' => $request->telephone,
                'adresse_ligne1' => $request->adresse_ligne1,
                'adresse_ligne2' => $request->adresse_ligne2,
                'ville' => $request->ville,
                'region' => $request->region,
                'code_postal' => $request->code_postal,
                'pays' => $request->pays,
                'par_defaut_expedition' => $request->par_defaut_expedition ?? false,
                'par_defaut_facturation' => $request->par_defaut_facturation ?? false,
                'date_creation' => now(),
                'date_modification' => now()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'data' => $adresse,
                'message' => 'Adresse ajoutée avec succès'
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/adresses/{id}",
     *     summary="Voir une adresse",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détails de l'adresse")
     * )
     */
    public function show(Request $request, $id)
    {
        try {
            $adresse = AdresseUtilisateur::where('utilisateur_id', $request->user()->id)
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $adresse
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Adresse non trouvée'
            ], 404);
        }
    }
    
    /**
     * @OA\Put(
     *     path="/api/adresses/{id}",
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
     *             @OA\Property(property="etiquette", type="string"),
     *             @OA\Property(property="nom_complet", type="string"),
     *             @OA\Property(property="telephone", type="string"),
     *             @OA\Property(property="adresse_ligne1", type="string"),
     *             @OA\Property(property="adresse_ligne2", type="string"),
     *             @OA\Property(property="ville", type="string"),
     *             @OA\Property(property="region", type="string"),
     *             @OA\Property(property="code_postal", type="string"),
     *             @OA\Property(property="pays", type="string"),
     *             @OA\Property(property="par_defaut_expedition", type="boolean"),
     *             @OA\Property(property="par_defaut_facturation", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Adresse modifiée")
     * )
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'etiquette' => 'nullable|string|max:50',
            'nom_complet' => 'string|max:190',
            'telephone' => 'nullable|string|max:30',
            'adresse_ligne1' => 'string|max:190',
            'adresse_ligne2' => 'nullable|string|max:190',
            'ville' => 'string|max:120',
            'region' => 'nullable|string|max:120',
            'code_postal' => 'nullable|string|max:20',
            'pays' => 'string|max:80',
            'par_defaut_expedition' => 'boolean',
            'par_defaut_facturation' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $adresse = AdresseUtilisateur::where('utilisateur_id', $request->user()->id)
                ->findOrFail($id);
            
            // Gestion des adresses par défaut
            if ($request->has('par_defaut_expedition') && $request->par_defaut_expedition) {
                AdresseUtilisateur::where('utilisateur_id', $request->user()->id)
                    ->where('id', '!=', $id)
                    ->where('par_defaut_expedition', true)
                    ->update(['par_defaut_expedition' => false]);
            }
            
            if ($request->has('par_defaut_facturation') && $request->par_defaut_facturation) {
                AdresseUtilisateur::where('utilisateur_id', $request->user()->id)
                    ->where('id', '!=', $id)
                    ->where('par_defaut_facturation', true)
                    ->update(['par_defaut_facturation' => false]);
            }
            
            $adresse->update($request->all());
            $adresse->date_modification = now();
            $adresse->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'data' => $adresse,
                'message' => 'Adresse modifiée avec succès'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Delete(
     *     path="/api/adresses/{id}",
     *     summary="Supprimer une adresse",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Adresse supprimée")
     * )
     */
    public function destroy(Request $request, $id)
    {
        try {
            $adresse = AdresseUtilisateur::where('utilisateur_id', $request->user()->id)
                ->findOrFail($id);
            
            $adresse->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Adresse supprimée avec succès'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Put(
     *     path="/api/adresses/{id}/defaut-expedition",
     *     summary="Définir comme adresse par défaut pour l'expédition",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Adresse par défaut définie")
     * )
     */
    public function setDefaultExpedition(Request $request, $id)
    {
        DB::beginTransaction();
        
        try {
            // Enlever le statut par défaut de toutes les adresses
            AdresseUtilisateur::where('utilisateur_id', $request->user()->id)
                ->update(['par_defaut_expedition' => false]);
            
            // Définir la nouvelle adresse par défaut
            $adresse = AdresseUtilisateur::where('utilisateur_id', $request->user()->id)
                ->findOrFail($id);
            
            $adresse->par_defaut_expedition = true;
            $adresse->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'data' => $adresse,
                'message' => 'Adresse d\'expédition par défaut définie'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/adresses/defaut/expedition",
     *     summary="Obtenir l'adresse par défaut pour l'expédition",
     *     tags={"Adresses"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Adresse par défaut")
     * )
     */
    public function getDefaultExpedition(Request $request)
    {
        try {
            $adresse = AdresseUtilisateur::where('utilisateur_id', $request->user()->id)
                ->where('par_defaut_expedition', true)
                ->first();
            
            return response()->json([
                'success' => true,
                'data' => $adresse,
                'message' => 'Adresse d\'expédition par défaut'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}
