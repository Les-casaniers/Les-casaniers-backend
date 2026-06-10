<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Models\NewsletterAbonnement;
use App\Mail\BienvenueMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log; 
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): Response
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.Utilisateur::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = Utilisateur::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

               // ✅ AJOUT : Ajouter l'utilisateur à la newsletter
        try {
            NewsletterAbonnement::updateOrCreate(
                ['email' => $user->email],
                [
                    'prenom' => explode(' ', $user->name)[0] ?? null, // Premier mot comme prénom
                    'nom' => explode(' ', $user->name)[1] ?? null,     // Deuxième mot comme nom
                    'actif' => true
                ]
            );

            // ✅ AJOUT : Envoyer l'email de bienvenue
            Mail::to($user->email)->send(new BienvenueMail($user));
            
        } catch (\Exception $e) {
            // On continue même si l'email échoue, l'utilisateur est déjà créé
            Log::error('Erreur envoi email bienvenue: ' . $e->getMessage());
        }

        event(new Registered($user));

        Auth::login($user);

        return response()->noContent();
    }
}
