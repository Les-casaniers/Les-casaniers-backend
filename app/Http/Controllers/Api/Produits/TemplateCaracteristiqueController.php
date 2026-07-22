<?php

namespace App\Http\Controllers\Api\Produits;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\TemplateCaracteristique;
use App\Models\SousCategorie;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class TemplateCaracteristiqueController extends Controller
{
    private function ensureAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || !($user instanceof Admin)) {
            return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }
        return null;
    }

    /**
     * Récupérer les templates d'une sous-catégorie
     */
    public function getBySousCategorie(int $sousCategorieId)
    {
        $templates = TemplateCaracteristique::where('sous_categorie_id', $sousCategorieId)
            ->orderBy('ordre_affichage')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }

    /**
     * Créer un template
     */
    public function store(Request $request)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        try {
            $validated = $request->validate([
                'sous_categorie_id' => 'required|exists:sous_categories,id',
                'nom_champ' => 'required|string|max:100',
                'type_champ' => 'required|in:texte,nombre,booleen,date',
                'ordre_affichage' => 'integer|min:0',
                'est_obligatoire' => 'boolean',
                'valeur_par_defaut' => 'nullable|string|max:255'
            ]);

            $exists = TemplateCaracteristique::where('sous_categorie_id', $validated['sous_categorie_id'])
                ->where('nom_champ', $validated['nom_champ'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce champ existe déjà pour cette sous-catégorie'
                ], 422);
            }

            $template = TemplateCaracteristique::create($validated);

            return response()->json([
                'success' => true,
                'data' => $template,
                'message' => 'Template créé avec succès'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mettre à jour un template
     */
    public function update(Request $request, int $id)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        try {
            $template = TemplateCaracteristique::findOrFail($id);

            $validated = $request->validate([
                'nom_champ' => 'sometimes|string|max:100',
                'type_champ' => 'sometimes|in:texte,nombre,booleen,date',
                'ordre_affichage' => 'sometimes|integer|min:0',
                'est_obligatoire' => 'sometimes|boolean',
                'valeur_par_defaut' => 'nullable|string|max:255'
            ]);

            $template->update($validated);

            return response()->json([
                'success' => true,
                'data' => $template,
                'message' => 'Template mis à jour'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer un template
     */
    public function destroy(Request $request, int $id)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        try {
            $template = TemplateCaracteristique::findOrFail($id);

            if ($template->valeursCaracteristiques()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce template car des valeurs y sont associées'
                ], 422);
            }

            $template->delete();

            return response()->json([
                'success' => true,
                'message' => 'Template supprimé'
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}