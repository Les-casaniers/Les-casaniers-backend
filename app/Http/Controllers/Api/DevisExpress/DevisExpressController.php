<?php

namespace App\Http\Controllers\Api\DevisExpress;

use App\Http\Controllers\Controller;
use App\Models\DevisExpress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Mail\DevisExpressConfirmation;
use App\Mail\DevisExpressAdminNotification;
use Illuminate\Support\Facades\Mail;


/**
 * @OA\Tag(
 *     name="Devis Express",
 *     description="Demandes de devis rapides"
 * )
 */
class DevisExpressController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/devis-express",
     *     summary="Soumettre une demande de devis express",
     *     tags={"Devis Express"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom", "email", "telephone", "besoin"},
     *             @OA\Property(property="nom", type="string", example="Jean Rakoto"),
     *             @OA\Property(property="email", type="string", example="jean@email.com"),
     *             @OA\Property(property="telephone", type="string", example="0341234567"),
     *             @OA\Property(property="entreprise", type="string", example="Ma Société"),
     *             @OA\Property(property="besoin", type="string", example="PC Gaming sur-mesure"),
     *             @OA\Property(property="budget", type="string", example="2 000 000 - 3 500 000 Ar"),
     *             @OA\Property(property="date_souhaitee", type="string", format="date", example="2025-12-31"),
     *             @OA\Property(property="message", type="string", example="Besoin d'un PC gaming puissant")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Demande soumise avec succès"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:190',
            'email' => 'required|email|max:190',
            'telephone' => 'required|string|max:30',
            'entreprise' => 'nullable|string|max:190',
            'besoin' => 'required|string|max:255',
            'budget' => 'nullable|string|max:100',
            'date_souhaitee' => 'nullable|date',
            'message' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Sauvegarder la demande
            $devis = DevisExpress::create([
                'nom' => $request->nom,
                'email' => $request->email,
                'telephone' => $request->telephone,
                'entreprise' => $request->entreprise,
                'besoin' => $request->besoin,
                'budget' => $request->budget,
                'date_souhaitee' => $request->date_souhaitee,
                'message' => $request->message,
                'statut' => 'en_attente'
            ]);

            // ENVOI EMAIL AU CLIENT
            Mail::to($request->email)->send(new DevisExpressConfirmation($devis));

            //ENVOI EMAIL À L'ADMIN (remplacez par l'email de l'admin)
            $adminEmail = 'onjaniainamapionona@gmail.com';
            Mail::to($adminEmail)->send(new DevisExpressAdminNotification($devis));

            // Optionnel: envoyer aussi aux admins multiples
            // $adminEmails = ['admin1@lescasaniers.mg', 'admin2@lescasaniers.mg'];
            // foreach ($adminEmails as $adminEmail) {
            //     Mail::to($adminEmail)->send(new DevisExpressAdminNotification($devis));
            // }

            return response()->json([
                'success' => true,
                'data' => $devis,
                'message' => 'Votre demande de devis a été envoyée avec succès. Vous allez recevoir un email de confirmation.'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur DevisExpress: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue. Veuillez réessayer.'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/devis-express",
     *     summary="Lister toutes les demandes (Admin)",
     *     tags={"Devis Express - Admin"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Liste des demandes")
     * )
     */
    public function adminList(Request $request)
    {
        try {
            $devis = DevisExpress::orderBy('created_at', 'desc')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $devis
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/devis-express/{id}",
     *     summary="Voir une demande (Admin)",
     *     tags={"Devis Express - Admin"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détails de la demande")
     * )
     */
    public function adminShow($id)
    {
        try {
            $devis = DevisExpress::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $devis
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Demande non trouvée'
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/devis-express/{id}/statut",
     *     summary="Changer le statut d'une demande (Admin)",
     *     tags={"Devis Express - Admin"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"statut"},
     *             @OA\Property(property="statut", type="string", enum={"en_attente", "traite", "repondu", "archive"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Statut mis à jour")
     * )
     */
    public function adminUpdateStatut(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:en_attente,traite,repondu,archive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $devis = DevisExpress::findOrFail($id);
            $devis->statut = $request->statut;
            $devis->save();

            return response()->json([
                'success' => true,
                'data' => $devis,
                'message' => 'Statut mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/devis-express/{id}",
     *     summary="Supprimer une demande (Admin)",
     *     tags={"Devis Express - Admin"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Demande supprimée")
     * )
     */
    public function adminDestroy($id)
    {
        try {
            $devis = DevisExpress::findOrFail($id);
            $devis->delete();

            return response()->json([
                'success' => true,
                'message' => 'Demande supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}
