<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminAuthController extends Controller
{
    protected $adminAuthService;

    public function __construct(AdminAuthService $adminAuthService)
    {
        $this->adminAuthService = $adminAuthService;
    }

    /**
     * @OA\Post(
     *     path="/api/admin/register",
     *     summary="Creer un compte administrateur",
     *     description="Permet de creer un nouveau compte administrateur.",
     *     tags={"Admin Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"prenom", "nom", "email", "mot_de_passe"},
     *             @OA\Property(property="prenom", type="string", example="Jean"),
     *             @OA\Property(property="nom", type="string", example="Dupont"),
     *             @OA\Property(property="email", type="string", format="email", example="admin@lescasaniers.com"),
     *             @OA\Property(property="telephone", type="string", example="0123456789"),
     *             @OA\Property(property="mot_de_passe", type="string", format="password", example="Secret123!"),
     *             @OA\Property(property="mot_de_passe_confirmation", type="string", format="password", example="Secret123!"),
     *             @OA\Property(property="poste", type="string", enum={"admin", "support", "logistique"}, example="admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte cree avec succes",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte administrateur cree avec succes"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur serveur")
     *         )
     *     )
     * )
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
                'poste',
            ]);

            $admin = $this->adminAuthService->register($payload);

            return response()->json([
                'success' => true,
                'message' => 'Compte administrateur cree avec succes',
                'data' => $admin,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Admin registration failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/login",
     *     summary="Connexion administrateur",
     *     description="Permet a un administrateur de se connecter via Sanctum stateful.",
     *     tags={"Admin Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "mot_de_passe"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@lescasaniers.com"),
     *             @OA\Property(property="mot_de_passe", type="string", format="password", example="Secret123!"),
     *             @OA\Property(property="remember", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion reussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion reussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="admin", type="object"),
     *                 @OA\Property(property="access_token", type="string", example="1|Abc..."),
     *                 @OA\Property(property="refresh_token", type="string", example="2|Xyz..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=1800)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides"
     *     )
     * )
     */
    public function login(Request $request)
    {
        try {
            $payload = $request->only([
                'email',
                'mot_de_passe',
                'remember',
            ]);

            $result = $this->adminAuthService->login(
                $payload,
                $request->ip(),
                $request->boolean('remember')
            );

            // Robust session handling: only regenerate if session is initialized
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'admin' => $result['admin'],
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
            Log::error('Admin login failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/logout",
     *     summary="Deconnexion administrateur",
     *     description="Deconnecte l'administrateur et revoque le token.",
     *     tags={"Admin Auth"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Deconnexion reussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Deconnexion reussie")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        try {
            $this->adminAuthService->logout('admin');

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie',
            ], 200);
        } catch (Throwable $e) {
            Log::error('Admin logout failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/admin/profile",
     *     summary="Profil administrateur connecte",
     *     description="Retourne les informations de l'administrateur actuellement connecte.",
     *     tags={"Admin Auth"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Succes",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifie"
     *     )
     * )
     */
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/admin/profile",
     *     summary="Modifier les informations de l'administrateur",
     *     description="Permet à l'administrateur connecté de modifier ses informations personnelles.",
     *     tags={"Admin Auth"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"prenom", "nom", "email"},
     *             @OA\Property(property="prenom", type="string", example="Jean"),
     *             @OA\Property(property="nom", type="string", example="Dupont"),
     *             @OA\Property(property="email", type="string", format="email", example="admin@lescasaniers.com"),
     *             @OA\Property(property="telephone", type="string", example="0123456789"),
     *             @OA\Property(property="poste", type="string", enum={"admin", "support", "logistique"}, example="admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profil mis à jour avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur"
     *     )
     * )
     */
    public function updateProfile(Request $request)
    {
        try {
            $payload = $request->only([
                'prenom',
                'nom',
                'email',
                'telephone',
                'poste',
            ]);

            $admin = $this->adminAuthService->updateProfile($request->user()->id, $payload);

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => $admin,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Admin profile update failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/change-password",
     *     summary="Changer le mot de passe de l'administrateur",
     *     description="Permet à l'administrateur de modifier son mot de passe en vérifiant l'ancien.",
     *     tags={"Admin Auth"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password", "new_password", "new_password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password", example="AncienMdp123!"),
     *             @OA\Property(property="new_password", type="string", format="password", example="NouveauMdp123!"),
     *             @OA\Property(property="new_password_confirmation", type="string", format="password", example="NouveauMdp123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mot de passe modifié avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Mot de passe modifié avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur"
     *     )
     * )
     */
    public function changePassword(Request $request)
    {
        try {
            $payload = $request->only([
                'current_password',
                'new_password',
                'new_password_confirmation',
            ]);

            $this->adminAuthService->changePassword($request->user()->id, $payload);

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
            Log::error('Admin password change failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/admin/refresh-token",
     *     summary="Renouveler l'access token via le refresh token",
     *     tags={"Admin Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="2|Xyz...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token renouvelé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string", example="3|NewAccess..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=1800)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Refresh token invalide ou expiré")
     * )
     */
    public function refreshToken(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string'
        ]);

        try {
            $data = $this->adminAuthService->refreshToken($request->refresh_token);

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

    /**
     * Admin - Liste tous les administrateurs
     */
    public function list(Request $request)
    {
        try {
            $admins = \App\Models\Admin::select(
                'id', 
                'prenom', 
                'nom', 
                'email', 
                'telephone', 
                'poste', 
                'statut', 
                'date_creation', 
                'date_modification'
            )
            ->orderBy('date_creation', 'desc')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $admins
            ], 200);
        } catch (Throwable $e) {
            Log::error('Admin list failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des administrateurs'
            ], 500);
        }
    }

    /**
     * Admin - Mettre à jour le statut d'un administrateur
     */
    public function updateStatut(Request $request, $id)
    {
        try {
            $request->validate([
                'statut' => 'required|in:actif,inactif'
            ]);

            $admin = \App\Models\Admin::find($id);
            
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Administrateur non trouvé'
                ], 404);
            }

            $admin->statut = $request->statut;
            $admin->save();

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'data' => $admin
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (Throwable $e) {
            Log::error('Admin update statut failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Admin - Supprimer un administrateur
     */
    public function destroy($id)
    {
        try {
            $admin = \App\Models\Admin::find($id);
            
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Administrateur non trouvé'
                ], 404);
            }

            // Empêcher la suppression de son propre compte
            if (auth()->id() == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas supprimer votre propre compte'
                ], 403);
            }

            $admin->delete();

            return response()->json([
                'success' => true,
                'message' => 'Administrateur supprimé avec succès'
            ], 200);
        } catch (Throwable $e) {
            Log::error('Admin destroy failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }
}
