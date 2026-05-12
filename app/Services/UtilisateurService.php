<?php

namespace App\Services;

use App\Repositories\UtilisateurRepositoryInterface;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class UtilisateurService
{
    protected $utilisateurRepository;

    public function __construct(UtilisateurRepositoryInterface $utilisateurRepository)
    {
        $this->utilisateurRepository = $utilisateurRepository;
    }

    /**
     * Inscription d'un nouvel utilisateur.
     */
    public function register(array $data)
    {
        $validator = Validator::make($data, [
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            'email' => 'required|email:rfc,dns|max:190|unique:utilisateurs,email',
            'telephone' => 'nullable|string|max:30',
            'mot_de_passe' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $payload = [
            'prenom' => trim($data['prenom']),
            'nom' => trim($data['nom']),
            'email' => Str::lower(trim($data['email'])),
            'telephone' => isset($data['telephone']) ? trim($data['telephone']) : null,
            'mot_de_passe' => $data['mot_de_passe'],
            'statut' => 'actif',
        ];

        return DB::transaction(function () use ($payload) {
            return $this->utilisateurRepository->create($payload);
        });
    }

    /**
     * Connexion d'un utilisateur.
     */
    public function login(array $data, string $ip, bool $remember = false)
    {
        $validator = Validator::make($data, [
            'email' => 'required|email:rfc,dns|max:190',
            'mot_de_passe' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $email = Str::lower(trim($data['email']));
        $throttleKey = $this->throttleKey($email, $ip);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout(request()));
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $utilisateur = $this->utilisateurRepository->findByEmail($email);

        if (!$utilisateur) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'email' => ['Adresse email incorrecte.'],
            ]);
        }

        if (!Hash::check($data['mot_de_passe'], $utilisateur->getAuthPassword())) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'mot_de_passe' => ['Mot de passe incorrect.'],
            ]);
        }

        Auth::guard('web')->login($utilisateur, $remember);


        RateLimiter::clear($throttleKey);
        
        $utilisateur = Auth::guard('web')->user();

        if ($utilisateur && $utilisateur->statut !== 'actif') {
            Auth::guard('web')->logout();
            throw ValidationException::withMessages([
                'email' => 'Votre compte est désactivé. Veuillez contacter le support.',
            ]);
        }

        // Génération des tokens Sanctum
        $accessToken = $utilisateur->createToken(
            'access_token', 
            ['access'], 
            Carbon::now()->addMinutes(config('sanctum.access_token_expires_in', 60))
        )->plainTextToken;

        $refreshToken = $utilisateur->createToken(
            'refresh_token', 
            ['refresh'], 
            Carbon::now()->addMinutes(config('sanctum.refresh_token_expires_in', 10080))
        )->plainTextToken;

        return [
            'utilisateur' => $utilisateur,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => config('sanctum.access_token_expires_in', 60) * 60
        ];
    }

    /**
     * Rafraîchissement du token.
     */
    public function refreshToken(string $refreshTokenString)
    {
        $token = PersonalAccessToken::findToken($refreshTokenString);

        if (!$token || !$token->can('refresh') || $token->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Le jeton de rafraîchissement est invalide ou expiré.'],
            ]);
        }

        $utilisateur = $token->tokenable;

        $newAccessToken = $utilisateur->createToken(
            'access_token', 
            ['access'], 
            Carbon::now()->addMinutes(config('sanctum.access_token_expires_in', 60))
        )->plainTextToken;

        return [
            'access_token' => $newAccessToken,
            'expires_in' => config('sanctum.access_token_expires_in', 60) * 60
        ];
    }

    /**
     * Déconnexion.
     */
    public function logout(): void
    {
        $utilisateur = Auth::guard('web')->user();
        if ($utilisateur) {
            $utilisateur->currentAccessToken()?->delete();
        }
        Auth::guard('web')->logout();
    }

    /**
     * Mise à jour du profil.
     */
    public function updateProfile(int $id, array $data)
    {
        $validator = Validator::make($data, [
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            'email' => 'required|email:rfc,dns|max:190|unique:utilisateurs,email,'.$id,
            'telephone' => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $payload = [
            'prenom' => trim($data['prenom']),
            'nom' => trim($data['nom']),
            'email' => Str::lower(trim($data['email'])),
            'telephone' => isset($data['telephone']) ? trim($data['telephone']) : null,
        ];

        return DB::transaction(function () use ($id, $payload) {
            return $this->utilisateurRepository->update($id, $payload);
        });
    }

    /**
     * Changement de mot de passe.
     */
    public function changePassword(int $id, array $data)
    {
        $validator = Validator::make($data, [
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'confirmed',
                'different:current_password',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $utilisateur = $this->utilisateurRepository->findById($id);

        if (!$utilisateur || !Hash::check($data['current_password'], $utilisateur->getAuthPassword())) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        return DB::transaction(function () use ($id, $data) {
            return $this->utilisateurRepository->update($id, [
                'mot_de_passe' => $data['new_password'],
            ]);
        });
    }

    private function throttleKey(string $email, string $ip): string
    {
        return Str::transliterate($email.'|'.$ip);
    }
}
