<?php

namespace App\Http\Controllers\Api\Produits;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ValeurCaracteristique;
use App\Models\TemplateCaracteristique;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class ValeurCaracteristiqueController extends Controller
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
     * Récupérer toutes les caractéristiques d'un produit
     */
    public function getByProduit(int $produitId)
    {
        $produit = Produit::findOrFail($produitId);

        $valeurs = ValeurCaracteristique::where('produit_id', $produitId)
            ->with('template')
            ->get();

        $data = $valeurs->map(function ($v) {
            return [
                'id' => $v->id,
                'produit_id' => $v->produit_id,
                'nom_champ' => $v->template->nom_champ,
                'valeur' => $v->valeur,
                'type_champ' => $v->template->type_champ,
                'est_obligatoire' => (bool) $v->template->est_obligatoire,
                'ordre_affichage' => $v->template->ordre_affichage
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Récupérer une caractéristique spécifique d'un produit
     */
    public function getByProduitAndChamp(int $produitId, string $nomChamp)
    {
        $produit = Produit::findOrFail($produitId);

        $valeur = ValeurCaracteristique::where('produit_id', $produitId)
            ->whereHas('template', function ($query) use ($nomChamp) {
                $query->where('nom_champ', $nomChamp);
            })
            ->with('template')
            ->first();

        if (!$valeur) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Caractéristique non trouvée'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $valeur->id,
                'produit_id' => $valeur->produit_id,
                'nom_champ' => $valeur->template->nom_champ,
                'valeur' => $valeur->valeur,
                'type_champ' => $valeur->template->type_champ,
                'est_obligatoire' => (bool) $valeur->template->est_obligatoire
            ]
        ]);
    }

    /**
     * Créer ou mettre à jour une valeur de caractéristique
     * Si le template n'existe pas, on le crée automatiquement
     */
    public function storeOrUpdate(Request $request)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        try {
            $validated = $request->validate([
                'produit_id' => 'required|exists:produits,id',
                'nom_champ' => 'required|string|max:100',
                'valeur' => 'required|string|max:500',
                'type_champ' => 'sometimes|in:texte,nombre,booleen,date',
                'est_obligatoire' => 'sometimes|boolean'
            ]);

            $produit = Produit::find($validated['produit_id']);

            // Chercher ou créer le template
            $template = TemplateCaracteristique::where('sous_categorie_id', $produit->id_sous_categorie)
                ->where('nom_champ', $validated['nom_champ'])
                ->first();

            if (!$template) {
                // Créer le template automatiquement
                $template = TemplateCaracteristique::create([
                    'sous_categorie_id' => $produit->id_sous_categorie,
                    'nom_champ' => $validated['nom_champ'],
                    'type_champ' => $validated['type_champ'] ?? 'texte',
                    'ordre_affichage' => 0,
                    'est_obligatoire' => $validated['est_obligatoire'] ?? false,
                    'valeur_par_defaut' => null
                ]);
            }

            // Créer ou mettre à jour la valeur
            $valeur = ValeurCaracteristique::updateOrCreate(
                [
                    'produit_id' => $validated['produit_id'],
                    'template_id' => $template->id
                ],
                [
                    'valeur' => $validated['valeur']
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $valeur->load('template'),
                'message' => 'Caractéristique sauvegardée'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer une valeur
     */
    public function destroy(Request $request, int $id)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        try {
            $valeur = ValeurCaracteristique::findOrFail($id);
            $valeur->delete();

            return response()->json([
                'success' => true,
                'message' => 'Caractéristique supprimée'
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Synchroniser toutes les caractéristiques d'un produit
     * Supprime les caractéristiques qui ne sont pas dans la liste
     */
    public function sync(Request $request, int $produitId)
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        try {
            $validated = $request->validate([
                'caracteristiques' => 'required|array',
                'caracteristiques.*.nom_champ' => 'required|string|max:100',
                'caracteristiques.*.valeur' => 'required|string|max:500',
                'caracteristiques.*.type_champ' => 'sometimes|in:texte,nombre,booleen,date',
                'caracteristiques.*.est_obligatoire' => 'sometimes|boolean'
            ]);

            $produit = Produit::findOrFail($produitId);

            $results = [];
            $templateIds = [];

            foreach ($validated['caracteristiques'] as $caract) {
                // Chercher ou créer le template
                $template = TemplateCaracteristique::where('sous_categorie_id', $produit->id_sous_categorie)
                    ->where('nom_champ', $caract['nom_champ'])
                    ->first();

                if (!$template) {
                    $template = TemplateCaracteristique::create([
                        'sous_categorie_id' => $produit->id_sous_categorie,
                        'nom_champ' => $caract['nom_champ'],
                        'type_champ' => $caract['type_champ'] ?? 'texte',
                        'ordre_affichage' => 0,
                        'est_obligatoire' => $caract['est_obligatoire'] ?? false,
                        'valeur_par_defaut' => null
                    ]);
                }

                $templateIds[] = $template->id;

                $valeur = ValeurCaracteristique::updateOrCreate(
                    [
                        'produit_id' => $produitId,
                        'template_id' => $template->id
                    ],
                    [
                        'valeur' => $caract['valeur']
                    ]
                );

                $results[] = $valeur->load('template');
            }

            // Supprimer les valeurs qui ne sont plus dans la liste
            ValeurCaracteristique::where('produit_id', $produitId)
                ->whereNotIn('template_id', $templateIds)
                ->delete();

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Caractéristiques synchronisées'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}