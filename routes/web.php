<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UtilisateurController extends Controller
{
    /**
     * Afficher la liste des utilisateurs (Web)
     */
    public function index()
    {
        $utilisateurs = Utilisateur::with('adresses')->orderBy('id', 'desc')->paginate(10);
        return view('utilisateurs.index', compact('utilisateurs'));
    }

    /**
     * Afficher le formulaire de création (non utilisé)
     */
    public function create()
    {
        // Retourne une vue de création si nécessaire
        // return view('utilisateurs.create');
    }

    /**
     * Enregistrer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:utilisateurs,email',
            'telephone' => 'nullable|string|max:20',
            'mot_de_passe' => 'required|string|min:8|confirmed',
            'statut' => 'boolean',
        ]);

        $utilisateur = Utilisateur::create($validated);

        return redirect()->route('utilisateurs.index')
                         ->with('success', 'Utilisateur créé avec succès');
    }

    /**
     * Afficher un utilisateur spécifique
     */
    public function show($id)
    {
        $utilisateur = Utilisateur::with('adresses')->findOrFail($id);
        return view('utilisateurs.show', compact('utilisateur'));
    }

    /**
     * Afficher le formulaire d'édition (non utilisé)
     */
    public function edit($id)
    {
        // Retourne une vue d'édition si nécessaire
        // $utilisateur = Utilisateur::findOrFail($id);
        // return view('utilisateurs.edit', compact('utilisateur'));
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, $id)
    {
        $utilisateur = Utilisateur::findOrFail($id);

        $validated = $request->validate([
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:utilisateurs,email,' . $id,
            'telephone' => 'nullable|string|max:20',
            'mot_de_passe' => 'nullable|string|min:8|confirmed',
            'statut' => 'boolean',
        ]);

        if (empty($validated['mot_de_passe'])) {
            unset($validated['mot_de_passe']);
        }

        $utilisateur->update($validated);

        return redirect()->route('utilisateurs.index')
                         ->with('success', 'Utilisateur mis à jour avec succès');
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy($id)
    {
        $utilisateur = Utilisateur::findOrFail($id);
        $utilisateur->delete();

        return redirect()->route('utilisateurs.index')
                         ->with('success', 'Utilisateur supprimé avec succès');
    }

    /**
     * Rechercher des utilisateurs
     */
    public function search(Request $request)
    {
        $query = Utilisateur::with('adresses');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('prenom', 'LIKE', "%{$search}%")
                  ->orWhere('nom', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('telephone', 'LIKE', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        if ($request->has('statut') && $request->statut !== '') {
            $query->where('statut', $request->statut);
        }

        $utilisateurs = $query->orderBy('id', 'desc')->paginate(10);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($utilisateurs);
        }

        return view('utilisateurs.index', compact('utilisateurs'));
    }

    /**
     * API: Récupérer tous les utilisateurs
     */
    public function getUsers(Request $request)
    {
        $utilisateurs = Utilisateur::with('adresses')->get();
        return response()->json([
            'success' => true,
            'data' => $utilisateurs,
            'count' => $utilisateurs->count()
        ]);
    }

    /**
     * API: Récupérer un utilisateur spécifique
     */
    public function getUser($id)
    {
        $utilisateur = Utilisateur::with('adresses')->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $utilisateur
        ]);
    }

    /**
     * API: Créer un utilisateur
     */
    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:utilisateurs,email',
            'telephone' => 'nullable|string|max:20',
            'mot_de_passe' => 'required|string|min:8',
            'statut' => 'boolean',
        ]);

        $utilisateur = Utilisateur::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'data' => $utilisateur
        ], 201);
    }

    /**
     * API: Mettre à jour un utilisateur
     */
    public function apiUpdate(Request $request, $id)
    {
        $utilisateur = Utilisateur::findOrFail($id);

        $validated = $request->validate([
            'prenom' => 'sometimes|string|max:255',
            'nom' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:utilisateurs,email,' . $id,
            'telephone' => 'nullable|string|max:20',
            'mot_de_passe' => 'nullable|string|min:8',
            'statut' => 'boolean',
        ]);

        if (empty($validated['mot_de_passe'])) {
            unset($validated['mot_de_passe']);
        }

        $utilisateur->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès',
            'data' => $utilisateur
        ]);
    }

    /**
     * API: Supprimer un utilisateur
     */
    public function apiDestroy($id)
    {
        $utilisateur = Utilisateur::findOrFail($id);
        $utilisateur->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }

    /**
     * Exporter les utilisateurs en CSV
     */
    public function exportCsv()
    {
        $utilisateurs = Utilisateur::all();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="utilisateurs.csv"',
        ];

        $callback = function() use ($utilisateurs) {
            $file = fopen('php://output', 'w');
            
            // En-têtes CSV
            fputcsv($file, ['ID', 'Prénom', 'Nom', 'Email', 'Téléphone', 'Statut', 'Date création']);
            
            // Données
            foreach ($utilisateurs as $utilisateur) {
                fputcsv($file, [
                    $utilisateur->id,
                    $utilisateur->prenom,
                    $utilisateur->nom,
                    $utilisateur->email,
                    $utilisateur->telephone,
                    $utilisateur->statut ? 'Actif' : 'Inactif',
                    $utilisateur->date_creation?->format('d/m/Y H:i')
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Exporter les utilisateurs en PDF (nécessite DomPDF ou autre)
     */
    public function exportPdf()
    {
        // Installation requise: composer require barryvdh/laravel-dompdf
        /*
        $utilisateurs = Utilisateur::all();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('utilisateurs.export-pdf', compact('utilisateurs'));
        return $pdf->download('utilisateurs.pdf');
        */
        
        return response()->json(['message' => 'Fonctionnalité à implémenter avec DomPDF']);
    }

    /**
     * Activation en masse des utilisateurs
     */
    public function bulkActivate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:utilisateurs,id'
        ]);

        Utilisateur::whereIn('id', $request->ids)->update(['statut' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateurs activés avec succès'
        ]);
    }

    /**
     * Suppression en masse des utilisateurs
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:utilisateurs,id'
        ]);

        Utilisateur::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateurs supprimés avec succès'
        ]);
    }
}