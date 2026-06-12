<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConsentementCookie;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class ConsentementCookieController extends Controller
{
    /**
     * Enregistrer le consentement de l'utilisateur
     */
    public function store(Request $request)
    {
        $request->validate([
            'choix' => 'required|in:accepter,refuser',
            'timestamp' => 'nullable|string'
        ]);

        $sessionId = session()->getId();
        $ipAddress = $request->ip();
        $choix = $request->input('choix');
        
        // Sauvegarder en base de données
        try {
            ConsentementCookie::create([
                'session_id' => $sessionId,
                'ip_address' => $ipAddress,
                'user_agent' => $request->userAgent(),
                'choix' => $choix,
                'timestamp' => $request->input('timestamp', now()),
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur sauvegarde consentement cookies: ' . $e->getMessage());
        }
        
        // Définir un cookie HTTP only pour suivre le consentement côté serveur
        $cookie = cookie(
            'cookie_consent_server', 
            $choix, 
            60 * 24 * 365, // 1 an
            '/',
            null,
            false,
            true // HttpOnly
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Consentement enregistré avec succès',
            'choix' => $choix
        ])->cookie($cookie);
    }
    
    /**
     * Vérifier l'état du consentement
     */
    public function check(Request $request)
    {
        $consent = $request->cookie('cookie_consent_server');
        
        if (!$consent) {
            // Vérifier en base de données
            $consentRecord = ConsentementCookie::where('session_id', session()->getId())
                ->latest()
                ->first();
                
            if ($consentRecord) {
                $consent = $consentRecord->choix;
            }
        }
        
        return response()->json([
            'consent_given' => !is_null($consent),
            'choix' => $consent
        ]);
    }
}