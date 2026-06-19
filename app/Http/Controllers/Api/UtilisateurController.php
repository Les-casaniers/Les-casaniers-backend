<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UtilisateurService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\NewsletterAbonnement;
use App\Mail\BienvenueMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class UtilisateurController extends Controller
{
    protected $utilisateurService;

    public function __construct(UtilisateurService $utilisateurService)
    {
        $this->utilisateurService = $utilisateurService;
    }

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        try {
            $payload = $request->only([
                'prenom',
                'nom',
                'email',
                'telephone',
                'mot_de_passe',
                'mot_de_passe_confirmation',
            ]);

            $utilisateur = $this->utilisateurService->register($payload);

            // Ajouter à newsletter et envoyer email de bienvenue
            try {
                NewsletterAbonnement::updateOrCreate(
                    ['email' => $utilisateur->email],
                    [
                        'prenom' => $request->prenom,
                        'nom' => $request->nom,
                        'actif' => true
                    ]
                );

                Mail::to($utilisateur->email)->send(new BienvenueMail($utilisateur));
                Log::info('Email de bienvenue envoyé à: ' . $utilisateur->email);
                
            } catch (\Exception $e) {
                Log::error('Erreur envoi email de bienvenue: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Compte utilisateur créé avec succès',
                'data' => $utilisateur,
            ], 201);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Utilisateur registration failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        try {
            $payload = $request->only(['email', 'mot_de_passe', 'password', 'remember']);
            $result = $this->utilisateurService->login($payload, $request->ip(), $request->boolean('remember'));

            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'utilisateur' => $result['utilisateur'],
                    'access_token' => $result['access_token'],
                    'refresh_token' => $result['refresh_token'],
                    'token_type' => 'Bearer',
                    'expires_in' => $result['expires_in']
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Utilisateur login failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        try {
            $this->utilisateurService->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie',
            ], 200);
        } catch (Throwable $e) {
            Log::error('Utilisateur logout failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion',
            ], 500);
        }
    }

    /**
     * Get user profile
     */
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $payload = $request->only(['prenom', 'nom', 'email', 'telephone']);
            $utilisateur = $this->utilisateurService->updateProfile($request->user()->id, $payload);

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => $utilisateur,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Utilisateur profile update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        try {
            $payload = $request->only(['current_password', 'new_password', 'new_password_confirmation']);
            $this->utilisateurService->changePassword($request->user()->id, $payload);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Utilisateur password change failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * Refresh token
     */
    public function refreshToken(Request $request)
    {
        $request->validate(['refresh_token' => 'required|string']);
        try {
            $data = $this->utilisateurService->refreshToken($request->refresh_token);
            return response()->json([
                'success' => true,
                'data' => [
                    'access_token' => $data['access_token'],
                    'token_type' => 'Bearer',
                    'expires_in' => $data['expires_in']
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 401);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    // ============================================
    // API METHODS FOR FRONTEND
    // ============================================

    /**
     * API: Liste des utilisateurs avec pagination
     */
    public function apiIndex(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);
            $search = $request->get('search', '');
            $statut = $request->get('statut', '');
            
            $clients = $this->utilisateurService->getAllClients($perPage, $search, $statut);
            
            return response()->json([
                'success' => true,
                'data' => $clients->items(),
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
            ]);
        } catch (Throwable $e) {
            Log::error('API Index error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Recherche avancée
     */
    public function apiSearch(Request $request)
    {
        try {
            $query = $request->get('q', '');
            $statut = $request->get('statut', '');
            
            $results = $this->utilisateurService->searchClients($query, $statut);
            
            return response()->json([
                'success' => true,
                'data' => $results,
                'count' => $results->count()
            ]);
        } catch (Throwable $e) {
            Log::error('API Search error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * API: Récupérer un utilisateur spécifique
     */
    public function apiShow($id)
    {
        try {
            $client = $this->utilisateurService->getClientById($id);
            return response()->json([
                'success' => true,
                'data' => $client,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
                'errors' => $e->errors(),
            ], 404);
        } catch (Throwable $e) {
            Log::error('API Show error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * API: Créer un utilisateur
     */
    public function apiStore(Request $request)
    {
        try {
            $payload = $request->only([
                'prenom',
                'nom',
                'email',
                'telephone',
                'mot_de_passe',
                'mot_de_passe_confirmation',
                'statut'
            ]);

            if (!isset($payload['statut'])) {
                $payload['statut'] = true;
            }

            $client = $this->utilisateurService->register($payload);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'data' => $client,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('API Store error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Mettre à jour un utilisateur
     */
    public function apiUpdate(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'prenom' => 'sometimes|required|string|max:100',
                'nom' => 'sometimes|required|string|max:100',
                'email' => 'sometimes|required|email:rfc,dns|max:190|unique:utilisateurs,email,' . $id,
                'telephone' => 'nullable|string|max:30',
                'statut' => 'sometimes|boolean',
            ]);

            $client = $this->utilisateurService->adminUpdateClient($id, $validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès',
                'data' => $client,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('API Update error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * API: Supprimer un utilisateur
     */
    public function apiDestroy($id)
    {
        try {
            $this->utilisateurService->deleteClient($id);
            return response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 404);
        } catch (Throwable $e) {
            Log::error('API Destroy error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * API: Exporter les utilisateurs en CSV
     */
    public function apiExportCsv()
    {
        try {
            $clients = $this->utilisateurService->getAllClientsWithoutPagination();
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="utilisateurs_' . date('Y-m-d') . '.csv"',
            ];

            $callback = function() use ($clients) {
                $file = fopen('php://output', 'w');
                
                fputcsv($file, ['ID', 'Prénom', 'Nom', 'Email', 'Téléphone', 'Statut', 'Date création']);
                
                foreach ($clients as $client) {
                    fputcsv($file, [
                        $client->id,
                        $client->prenom,
                        $client->nom,
                        $client->email,
                        $client->telephone ?? '',
                        $client->statut ? 'Actif' : 'Inactif',
                        $client->date_creation?->format('d/m/Y H:i') ?? ''
                    ]);
                }
                
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (Throwable $e) {
            Log::error('API Export error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Activation en masse
     */
    public function apiBulkActivate(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:utilisateurs,id'
            ]);

            $count = $this->utilisateurService->bulkActivate($request->ids);

            return response()->json([
                'success' => true,
                'message' => "{$count} utilisateur(s) activé(s) avec succès",
                'count' => $count
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('API Bulk Activate error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * API: Suppression en masse
     */
    public function apiBulkDelete(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:utilisateurs,id'
            ]);

            $count = $this->utilisateurService->bulkDelete($request->ids);

            return response()->json([
                'success' => true,
                'message' => "{$count} utilisateur(s) supprimé(s) avec succès",
                'count' => $count
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('API Bulk Delete error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    // ============================================
    // ADMIN METHODS
    // ============================================

    public function adminIndex(Request $request)
    {
        return $this->apiIndex($request);
    }

    public function adminStore(Request $request)
    {
        return $this->apiStore($request);
    }

    public function adminShow($id)
    {
        return $this->apiShow($id);
    }

    public function adminUpdate(Request $request, $id)
    {
        return $this->apiUpdate($request, $id);
    }

    public function adminDestroy($id)
    {
        return $this->apiDestroy($id);
    }

    public function adminSearch(Request $request)
    {
        return $this->apiSearch($request);
    }

    public function adminExportCsv()
    {
        return $this->apiExportCsv();
    }

    public function adminBulkActivate(Request $request)
    {
        return $this->apiBulkActivate($request);
    }

    public function adminBulkDelete(Request $request)
    {
        return $this->apiBulkDelete($request);
    }
}
