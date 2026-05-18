<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    /**
     * Subscribe an email to the newsletter (public).
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'website' => 'nullable|size:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $rateKey = Str::lower($request->ip() . '|' . $request->input('email'));
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Trop de tentatives. Reessayez dans quelques minutes.',
            ], 429);
        }
        RateLimiter::hit($rateKey, 300);

        $email = strtolower(trim($request->input('email')));

        $existing = NewsletterSubscriber::where('email', $email)->first();

        if ($existing) {
            if ($existing->actif) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette adresse email est déjà inscrite à la newsletter.',
                ], 409);
            }

            // Re-activate
            $existing->update([
                'actif' => true,
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Votre inscription a été réactivée avec succès !',
            ]);
        }

        NewsletterSubscriber::create([
            'email' => $email,
            'source' => $request->input('source', 'site'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Inscription à la newsletter réussie !',
        ], 201);
    }

    /**
     * Unsubscribe from the newsletter (public).
     */
    public function unsubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $subscriber = NewsletterSubscriber::where('email', strtolower(trim($request->input('email'))))->first();

        if (!$subscriber || !$subscriber->actif) {
            return response()->json(['success' => false, 'message' => 'Email non trouvé.'], 404);
        }

        $subscriber->update([
            'actif' => false,
            'unsubscribed_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Désinscription effectuée.']);
    }

    /**
     * Admin: list subscribers with pagination and filters.
     */
    public function adminIndex(Request $request)
    {
        $user = $request->user();
        if (!$user || !($user instanceof Admin)) {
            return response()->json(['success' => false, 'message' => 'Acces refuse'], 403);
        }

        $query = NewsletterSubscriber::query();

        if ($request->has('actif')) {
            $query->where('actif', filter_var($request->query('actif'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($search = $request->query('search')) {
            $query->where('email', 'like', "%{$search}%");
        }

        $query->orderByDesc('subscribed_at');

        return response()->json([
            'success' => true,
            'data' => $query->paginate((int) ($request->query('per_page', 20))),
        ]);
    }

    /**
     * Admin: delete a subscriber.
     */
    public function adminDestroy(Request $request, int $id)
    {
        $user = $request->user();
        if (!$user || !($user instanceof Admin)) {
            return response()->json(['success' => false, 'message' => 'Acces refuse'], 403);
        }

        $subscriber = NewsletterSubscriber::find($id);
        if (!$subscriber) {
            return response()->json(['success' => false, 'message' => 'Abonné non trouvé.'], 404);
        }

        $subscriber->delete();

        return response()->json(['success' => true, 'message' => 'Abonné supprimé.']);
    }
}
