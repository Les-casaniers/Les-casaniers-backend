<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UtilisateurService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
     * @OA\Post(
     *     path="/utilisateurs/register",
     *     summary="Créer un compte utilisateur",
     *     description="Permet de créer un nouveau compte utilisateur.",
     *     tags={"Utilisateurs"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"prenom", "nom", "email", "mot_de_passe", "mot_de_passe_confirmation"},
     *             @OA\Property(property="prenom", type="string", example="Jean"),
     *             @OA\Property(property="nom", type="string", example="Dupont"),
     *             @OA\Property(property="email", type="string", format="email", example="jean.dupont@email.com"),
     *             @OA\Property(property="telephone", type="string", example="0123456789"),
     *             @OA\Property(property="mot_de_passe", type="string", format="password", example="Secret123!"),
     *             @OA\Property(property="mot_de_passe_confirmation", type="string", format="password", example="Secret123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte utilisateur créé avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Erreur de validation")
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
            ]);

            $utilisateur = $this->utilisateurService->register($payload);

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
     * @OA\Post(
     *     path="/utilisateurs/login",
     *     summary="Connexion utilisateur",
     *     tags={"Utilisateurs"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "mot_de_passe"},
     *             @OA\Property(property="email", type="string", example="jean.dupont@email.com"),
     *             @OA\Property(property="mot_de_passe", type="string", example="Secret123!"),
     *             @OA\Property(property="remember", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Connexion réussie")
     * )
     */
    public function login(Request $request)
    {
        try {
            $payload = $request->only(['email', 'mot_de_passe', 'remember']);
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
     * @OA\Post(
     *     path="/utilisateurs/logout",
     *     summary="Déconnexion utilisateur",
     *     tags={"Utilisateurs"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Déconnexion réussie")
     * )
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
     * @OA\Get(
     *     path="/utilisateurs/profile",
     *     summary="Profil utilisateur connecté",
     *     tags={"Utilisateurs"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Succès")
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
     *     path="/utilisateurs/profile",
     *     summary="Modifier le profil",
     *     tags={"Utilisateurs"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Profil mis à jour")
     * )
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
     * @OA\Post(
     *     path="/utilisateurs/change-password",
     *     summary="Changer le mot de passe",
     *     tags={"Utilisateurs"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(response=200, description="Mot de passe modifié")
     * )
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
     * @OA\Post(
     *     path="/change-password",
     *     summary="Réinitialiser le mot de passe utilisateur",
     *     description="Réinitialise le mot de passe d'un utilisateur à partir de son email.",
     *     tags={"Utilisateurs"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
    *             required={"email", "current_password", "new_password", "new_password_confirmation"},
     *             @OA\Property(property="email", type="string", format="email", example="jean.dupont@email.com"),
    *             @OA\Property(property="current_password", type="string", format="password", example="AncienMdp123!"),
     *             @OA\Property(property="new_password", type="string", format="password", example="NouveauMdp123!"),
     *             @OA\Property(property="new_password_confirmation", type="string", format="password", example="NouveauMdp123!")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Mot de passe mis à jour"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function changePasswordByEmail(Request $request)
    {
        try {
            $payload = $request->only(['email', 'current_password', 'new_password', 'new_password_confirmation']);
            $this->utilisateurService->resetPasswordByEmail($payload);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe mis à jour avec succès',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Utilisateur reset password failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/utilisateurs/refresh-token",
     *     summary="Rafraîchir le token",
     *     tags={"Utilisateurs"},
     *     @OA\Response(response=200, description="Token rafraîchi")
     * )
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
}
