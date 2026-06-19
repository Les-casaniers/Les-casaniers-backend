<?php
// app/Services/AdminAuthService.php

namespace App\Services;

use App\Repositories\AdminRepositoryInterface;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class AdminAuthService
{
    protected $adminRepository;

    public function __construct(AdminRepositoryInterface $adminRepository)
    {
        $this->adminRepository = $adminRepository;
    }

    public function register(array $data)
    {
        $validator = Validator::make($data, [
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            'email' => 'required|email|max:190|unique:admin,email',
            'telephone' => 'nullable|string|max:30',
            'mot_de_passe' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
            'poste' => 'nullable|in:admin,support,logistique,livreur',
        ]);

        if ($validator->fails()) {
            Log::error('Admin registration validation failed', ['errors' => $validator->errors()->toArray(), 'data' => $data]);
            throw new ValidationException($validator);
        }

        Log::info('Attempting to create admin', ['email' => $data['email'] ?? null]);

        $payload = [
            'prenom' => trim($data['prenom']),
            'nom' => trim($data['nom']),
            'email' => Str::lower(trim($data['email'])),
            'telephone' => isset($data['telephone']) ? trim($data['telephone']) : null,
            'mot_de_passe' => $data['mot_de_passe'],
            'poste' => $data['poste'] ?? 'admin',
            'statut' => 'actif',
        ];

        return DB::transaction(function () use ($payload) {
            return $this->adminRepository->create($payload);
        });
    }

    public function login(array $data, string $ip, bool $remember = false)
    {
        if (!isset($data['mot_de_passe']) && isset($data['password'])) {
            $data['mot_de_passe'] = $data['password'];
        }

        $validator = Validator::make($data, [
            'email' => 'required|email|max:190',
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

        $admin = $this->adminRepository->findByEmail($email);

        if (!$admin) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'email' => ['Adresse email incorrecte.'],
            ]);
        }

        if (!Hash::check($data['mot_de_passe'], $admin->getAuthPassword())) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'mot_de_passe' => ['Mot de passe incorrect.'],
            ]);
        }

        if ($admin->statut !== 'actif') {
            throw ValidationException::withMessages([
                'email' => 'Votre compte est désactivé. Veuillez contacter l\'administration.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $accessToken = $admin->createToken(
            'access_token',
            ['access'],
            Carbon::now()->addMinutes(config('sanctum.access_token_expires_in', 30))
        )->plainTextToken;

        $refreshToken = $admin->createToken(
            'refresh_token',
            ['refresh'],
            Carbon::now()->addMinutes(config('sanctum.refresh_token_expires_in', 10080))
        )->plainTextToken;

        return [
            'admin' => $admin,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => config('sanctum.access_token_expires_in', 30) * 60
        ];
    }

    public function refreshToken(string $refreshTokenString)
    {
        $token = PersonalAccessToken::findToken($refreshTokenString);

        if (!$token || !$token->can('refresh') || $token->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Le jeton de rafraîchissement est invalide ou expiré.'],
            ]);
        }

        $admin = $token->tokenable;

        $newAccessToken = $admin->createToken(
            'access_token',
            ['access'],
            Carbon::now()->addMinutes(config('sanctum.access_token_expires_in', 30))
        )->plainTextToken;

        return [
            'access_token' => $newAccessToken,
            'expires_in' => config('sanctum.access_token_expires_in', 30) * 60
        ];
    }

    public function logout(string $guard = 'admin'): void
    {
        $admin = Auth::guard($guard)->user();

        if ($admin) {
            if (method_exists($admin, 'currentAccessToken')) {
                $admin->currentAccessToken()?->delete();
            } else {
                // Fallback: revoke all personal access tokens for this admin if currentAccessToken() isn't available
                PersonalAccessToken::where('tokenable_id', $admin->getAuthIdentifier())
                    ->where('tokenable_type', get_class($admin))
                    ->delete();
            }
        }

        Auth::guard($guard)->logout();
    }

    public function updateProfile(int $adminId, array $data)
    {
        $validator = Validator::make($data, [
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            'email' => 'required|email|max:190|unique:admin,email,' . $adminId,
            'telephone' => 'nullable|string|max:30',
            'poste' => 'nullable|in:admin,support,logistique',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $payload = [
            'prenom' => trim($data['prenom']),
            'nom' => trim($data['nom']),
            'email' => Str::lower(trim($data['email'])),
            'telephone' => isset($data['telephone']) ? trim($data['telephone']) : null,
            'poste' => $data['poste'] ?? 'admin',
        ];

        return DB::transaction(function () use ($adminId, $payload) {
            return $this->adminRepository->update($adminId, $payload);
        });
    }

    public function changePassword(int $adminId, array $data)
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
        ], [
            'new_password.different' => 'Le nouveau mot de passe doit être différent de l\'actuel.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $admin = $this->adminRepository->findById($adminId);

        if (!$admin || !Hash::check($data['current_password'], $admin->getAuthPassword())) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        return DB::transaction(function () use ($adminId, $data) {
            return $this->adminRepository->update($adminId, [
                'mot_de_passe' => $data['new_password'],
            ]);
        });
    }

    private function throttleKey(string $email, string $ip): string
    {
        return Str::transliterate($email . '|' . $ip);
    }
}
