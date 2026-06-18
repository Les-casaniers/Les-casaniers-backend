<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifie',
            ], 401);
        }

        if (empty($roles)) {
            $roles = ['livreur', 'admin'];
        }

        foreach ($roles as $role) {
            if (($user->poste ?? null) === $role) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Acces non autorise. Vous devez etre livreur ou administrateur.',
            'user_role' => $user->poste ?? null,
        ], 403);
    }
}
