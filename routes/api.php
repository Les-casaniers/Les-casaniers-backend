<?php

use App\Http\Controllers\Api\Adresse\AdresseController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AdminNotificationController;
use App\Http\Controllers\Api\AvisClients\AvisClientController;
use App\Http\Controllers\Api\Favoris\FavorisController;
use App\Http\Controllers\Api\Guides\GuideController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\Paniers\PanierController;
use App\Http\Controllers\Api\Produits\AttributProduitController;
use App\Http\Controllers\Api\Produits\CategoryController;
use App\Http\Controllers\Api\Produits\SousCategorieController;
use App\Http\Controllers\Api\Produits\ConfigurationController;
use App\Http\Controllers\Api\Produits\ImageProduitController;
use App\Http\Controllers\Api\Produits\ProduitController;
use App\Http\Controllers\Api\Sales\CommandeController;
use App\Http\Controllers\Api\Sales\DevisController;
use App\Http\Controllers\Api\Sales\FactureController;
use App\Http\Controllers\Api\Admin\AdminFavorisController;//favoris Admin
use App\Http\Controllers\Api\Admin\AdminPanierController;//panier Admin
use App\Http\Controllers\Api\BoutiqueMisa\BoutiqueMisaController;//boutique misa
use App\Http\Controllers\Api\Livreur\LivreurController;//Livreur
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UtilisateurController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use App\Http\Controllers\Api\DevisExpress\DevisExpressController;
use App\Models\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\ConsentementCookieController;

Route::middleware(['auth:sanctum'])->get('/user', [UserController::class, 'user']);

Route::prefix('admin')->group(function () {
    Route::post('/register', [AdminAuthController::class, 'register'])
        ->withoutMiddleware([EnsureFrontendRequestsAreStateful::class]);

    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/refresh-token', [AdminAuthController::class, 'refreshToken']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [AdminAuthController::class, 'profile']);
        Route::put('/profile', [AdminAuthController::class, 'updateProfile']);
        Route::post('/change-password', [AdminAuthController::class, 'changePassword']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);
    });
});

Route::prefix('utilisateurs')->group(function () {
    Route::post('/register', [UtilisateurController::class, 'register']);
    Route::post('/login', [UtilisateurController::class, 'login']);
    Route::post('/refresh-token', [UtilisateurController::class, 'refreshToken']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [UtilisateurController::class, 'profile']);
        Route::put('/profile', [UtilisateurController::class, 'updateProfile']);
        Route::post('/change-password', [UtilisateurController::class, 'changePassword']);
        Route::post('/logout', [UtilisateurController::class, 'logout']);
    });
});

// Public routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/sous-categories', [SousCategorieController::class, 'index']);
Route::get('/sous-categories/{id}', [SousCategorieController::class, 'show']);
Route::get('/produits', [ProduitController::class, 'index']);
Route::get('/produits/{id}', [ProduitController::class, 'show']);
Route::get('/produits/{produitId}/avis', [AvisClientController::class, 'getAvisByProduit']);
Route::get('/produits/{produitId}/avis/stats', [AvisClientController::class, 'getStatistiquesProduit']);
Route::get('/avis/latest', [AvisClientController::class, 'latest']);
Route::get('/guides/recent', [GuideController::class, 'recent']);
Route::get('/guides/popular', [GuideController::class, 'popular']);
Route::get('/guides/featured', [GuideController::class, 'featured']);
Route::get('/guides/categories', [GuideController::class, 'categories']);
Route::get('/guides/categorie/{categorie}', [GuideController::class, 'byCategory']);
Route::get('/guides', [GuideController::class, 'index']);
Route::get('/guides/{id}', [GuideController::class, 'show'])->whereNumber('id');
Route::get('/guides/slug/{slug}', [GuideController::class, 'showBySlug']);

//cookies
Route::post('/cookies/consent', [ConsentementCookieController::class, 'store']);
Route::get('/cookies/consent/check', [ConsentementCookieController::class, 'check']);

// Newsletter
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);
Route::post('/newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe']);

// Protected product/admin routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::patch('/categories/reorder', [CategoryController::class, 'reorder']);

    Route::post('/sous-categories', [SousCategorieController::class, 'store']);
    Route::put('/sous-categories/{id}', [SousCategorieController::class, 'update']);
    Route::delete('/sous-categories/{id}', [SousCategorieController::class, 'destroy']);

    Route::post('/produits', [ProduitController::class, 'store']);
    Route::put('/produits/{id}', [ProduitController::class, 'update']);
    Route::delete('/produits/{id}', [ProduitController::class, 'destroy']);
    Route::patch('/produits/{id}/toggle-status', [ProduitController::class, 'toggleStatus']);

    Route::post('/produits/{produitId}/images', [ImageProduitController::class, 'store']);
    Route::delete('/images/{id}', [ImageProduitController::class, 'destroy']);
    Route::patch('/produits/{produitId}/images/{imageId}/set-main', [ImageProduitController::class, 'setMain']);

    Route::post('/produits/{produitId}/attributes/sync', [AttributProduitController::class, 'sync']);
    Route::get('/attributes/standard-keys', [AttributProduitController::class, 'getStandardKeys']);
});

// Sales routes
Route::middleware(['auth:sanctum'])->prefix('commandes')->group(function () {
    Route::get('/', [CommandeController::class, 'index']);
    Route::get('/{uuid}', [CommandeController::class, 'show']);
    Route::post('/', [CommandeController::class, 'store']);
    Route::patch('/{uuid}/statut', [CommandeController::class, 'updateStatus']);
    Route::post('/{uuid}/cancel', [CommandeController::class, 'cancel']);
});

Route::middleware(['auth:sanctum'])->prefix('devis')->group(function () {
    Route::get('/', [DevisController::class, 'index']);
    Route::get('/{id}', [DevisController::class, 'show']);
    Route::post('/', [DevisController::class, 'store']);
    Route::put('/{id}', [DevisController::class, 'update']);
    Route::post('/{id}/envoyer', [DevisController::class, 'envoyer']);
    Route::post('/{id}/accepter', [DevisController::class, 'accepter']);
    Route::post('/{id}/refuser', [DevisController::class, 'refuser']);
    Route::post('/{id}/expirer', [DevisController::class, 'expirer']);
    Route::delete('/{id}', [DevisController::class, 'destroy']);
});

// Route::middleware(['auth:sanctum'])->prefix('factures')->group(function () {
//     Route::get('/', [FactureController::class, 'index']);
//     Route::get('/{id}', [FactureController::class, 'show']);
//     Route::get('/{id}/download', [FactureController::class, 'download']);
// });

/// ROute pour la facturation pour le moment
Route::middleware(['auth:sanctum'])->prefix('factures')->group(function () {
    Route::get('/', [FactureController::class, 'index']);
    Route::get('/{id}', [FactureController::class, 'show']);
    Route::post('/generate', [FactureController::class, 'generate']);
    Route::post('/{id}/pay', [FactureController::class, 'markAsPaid']);
});

// Avis routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/mes-avis', [AvisClientController::class, 'getMesAvis']);
    Route::get('/avis/search', [AvisClientController::class, 'search']);
    Route::get('/avis/{id}', [AvisClientController::class, 'show']);
    Route::post('/avis', [AvisClientController::class, 'store']);
    Route::put('/avis/{id}', [AvisClientController::class, 'update']);
    Route::delete('/avis/{id}', [AvisClientController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/avis', [AvisClientController::class, 'adminList']);
    Route::put('/avis/{id}/publier', [AvisClientController::class, 'togglePublish']);
    Route::get('/guides', [GuideController::class, 'adminIndex']);
    Route::post('/guides', [GuideController::class, 'store']);
    Route::get('/guides/{id}', [GuideController::class, 'adminShow']);
    Route::post('/guides/{id}', [GuideController::class, 'update']);
    Route::put('/guides/{id}', [GuideController::class, 'update']);
    Route::delete('/guides/{id}', [GuideController::class, 'destroy']);

    // Newsletter admin
    Route::get('/newsletter', [NewsletterController::class, 'adminIndex']);
    Route::delete('/newsletter/{id}', [NewsletterController::class, 'adminDestroy']);
    Route::post('/utilisateurs', [UtilisateurController::class, 'adminStore']);
    Route::get('/utilisateurs', [UtilisateurController::class, 'adminIndex']);
    Route::get('/utilisateurs/{id}', [UtilisateurController::class, 'adminShow']);
    Route::put('/utilisateurs/{id}', [UtilisateurController::class, 'adminUpdate']);
    Route::delete('/utilisateurs/{id}', [UtilisateurController::class, 'adminDestroy']);
    Route::get('/commandes', [CommandeController::class, 'adminIndex']);
    Route::post('/commandes', [CommandeController::class, 'adminStore']);
    Route::get('/commandes/{uuid}', [CommandeController::class, 'adminShow']);
    Route::patch('/commandes/{uuid}/statut', [CommandeController::class, 'adminUpdateStatus']);
    Route::post('/commandes/{uuid}/cancel', [CommandeController::class, 'adminCancel']);
    Route::get('/devis', [DevisController::class, 'adminIndex']);
    Route::post('/devis', [DevisController::class, 'adminStore']);
    Route::get('/factures', [FactureController::class, 'adminIndex']);
    Route::post('/factures', [FactureController::class, 'adminStore']);
    Route::get('/factures/{id}', [FactureController::class, 'adminShow']);
    Route::post('/factures/{id}/emettre', [FactureController::class, 'adminEmit']);
    Route::post('/factures/{id}/payer', [FactureController::class, 'adminMarkPaid']);
    Route::post('/factures/{id}/annuler', [FactureController::class, 'adminCancel']);
    Route::get('/factures/{id}/download', [FactureController::class, 'adminDownload']);
    Route::get('/utilisateurs/{utilisateurId}/adresses', [AdresseController::class, 'adminIndexByUser']);
    Route::post('/utilisateurs/{utilisateurId}/adresses', [AdresseController::class, 'adminStoreForUser']);
    Route::put('/utilisateurs/{utilisateurId}/adresses/{id}', [AdresseController::class, 'adminUpdateForUser']);
    Route::delete('/utilisateurs/{utilisateurId}/adresses/{id}', [AdresseController::class, 'adminDestroyForUser']);

    // ─── Notifications admin ────────────────────────────────
    Route::get('/notifications', [AdminNotificationController::class, 'index']);
    Route::get('/notifications/count', [AdminNotificationController::class, 'count']);
    Route::patch('/notifications/lire-tout', [AdminNotificationController::class, 'markAllAsRead']);
    Route::patch('/notifications/{id}/lire', [AdminNotificationController::class, 'markAsRead']);
    Route::delete('/notifications/all', [AdminNotificationController::class, 'destroyAll']);
    Route::delete('/notifications/{id}', [AdminNotificationController::class, 'destroy']);

    // Routes pour la gestion des administrateurs
    Route::get('/list', [AdminAuthController::class, 'list']);
    Route::put('/{id}/statut', [AdminAuthController::class, 'updateStatut']);
    Route::delete('/{id}', [AdminAuthController::class, 'destroy']);
});

// Panier routes
Route::middleware(['auth:sanctum'])->prefix('panier')->group(function () {
    Route::get('/', [PanierController::class, 'index']);
    Route::post('/ajouter', [PanierController::class, 'ajouter']);
    Route::put('/modifier/{itemId}', [PanierController::class, 'modifierQuantite']);
    Route::delete('/supprimer/{itemId}', [PanierController::class, 'supprimer']);
    Route::delete('/vider', [PanierController::class, 'vider']);
});

// Routes devis (client connecté)
// Route::middleware(['auth:sanctum'])->prefix('devis')->group(function () {
//     Route::get('/', [DevisController::class, 'index']);              // Mes devis
//     Route::post('/creer', [DevisController::class, 'creer']);        // Créer un devis
//     Route::get('/{id}', [DevisController::class, 'show']);           // Voir un devis
//     Route::put('/{id}/envoyer', [DevisController::class, 'envoyer']); // Envoyer un devis
//     Route::delete('/{id}', [DevisController::class, 'destroy']);     // Supprimer un devis
// });

use App\Http\Controllers\Api\Configurateur\ProfilConfigurateurController;

// Routes publiques pour les profils configurateur
Route::get('/profils-configurateur', [ProfilConfigurateurController::class, 'index']);
Route::get('/profils-configurateur/{slug}', [ProfilConfigurateurController::class, 'show']);

// Routes admin (protégées)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::post('/profils-configurateur', [ProfilConfigurateurController::class, 'store']);
    Route::put('/profils-configurateur/{id}', [ProfilConfigurateurController::class, 'update']);
    Route::delete('/profils-configurateur/{id}', [ProfilConfigurateurController::class, 'destroy']);
});



// Routes adresses (client connecté)
Route::middleware(['auth:sanctum'])->prefix('adresses')->group(function () {
    Route::get('/', [AdresseController::class, 'index']);
    Route::post('/', [AdresseController::class, 'store']);
    Route::get('/{id}', [AdresseController::class, 'show']);
    Route::put('/{id}', [AdresseController::class, 'update']);
    Route::delete('/{id}', [AdresseController::class, 'destroy']);
    Route::put('/{id}/defaut-expedition', [AdresseController::class, 'setDefaultExpedition']);
    Route::get('/defaut/expedition', [AdresseController::class, 'getDefaultExpedition']);
});
// Favoris routes
Route::middleware(['auth:sanctum'])->prefix('favoris')->group(function () {
    Route::get('/', [FavorisController::class, 'index']);
    Route::post('/', [FavorisController::class, 'store']);
    Route::delete('/{produitId}', [FavorisController::class, 'destroy']);
});

// Configurations routes
Route::middleware(['auth:sanctum'])->prefix('configurations')->group(function () {
    Route::get('/', [ConfigurationController::class, 'index']);
    Route::post('/', [ConfigurationController::class, 'store']);
    Route::put('/{id}', [ConfigurationController::class, 'update']);
    Route::delete('/{id}', [ConfigurationController::class, 'destroy']);
});

// Adresses routes
Route::middleware(['auth:sanctum'])->prefix('adresses')->group(function () {
    Route::get('/', [AdresseController::class, 'index']);
    Route::post('/', [AdresseController::class, 'store']);
    Route::get('/defaut/expedition', [AdresseController::class, 'getDefaultExpedition']);
    Route::get('/{id}', [AdresseController::class, 'show']);
    Route::put('/{id}', [AdresseController::class, 'update']);
    Route::delete('/{id}', [AdresseController::class, 'destroy']);
    Route::put('/{id}/defaut-expedition', [AdresseController::class, 'setDefaultExpedition']);
    // ✅ Route pour uploader l'image
    Route::post('/upload-image', [AdresseController::class, 'uploadImage']);
});

// Route::middleware(['auth:sanctum', 'admin'])->prefix('adresses')->group(function () {
//     Route::delete('/{id}', [AdresseController::class, 'destroy']);
// });



// Route publique pour soumettre un devis express
Route::post('/devis-express', [DevisExpressController::class, 'store']);

// Routes admin (protégées)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/devis-express', [DevisExpressController::class, 'adminList']);
    Route::get('/devis-express/{id}', [DevisExpressController::class, 'adminShow']);
    Route::put('/devis-express/{id}/statut', [DevisExpressController::class, 'adminUpdateStatut']);
    Route::delete('/devis-express/{id}', [DevisExpressController::class, 'adminDestroy']);
});

// Ajoutez cette route dans le groupe des routes authentifiées
Route::get('/commandes/last', [CommandeController::class, 'getLastNumber']);

// // Routes pour les devis
Route::middleware(['auth:sanctum'])->prefix('devis')->group(function () {
    Route::get('/', [DevisController::class, 'index']);      // GET /api/devis
    Route::post('/', [DevisController::class, 'store']);     // POST /api/devis
    Route::get('/{id}', [DevisController::class, 'show']);   // GET /api/devis/{id}
    Route::put('/{id}', [DevisController::class, 'update']); // PUT /api/devis/{id}
    Route::delete('/{id}', [DevisController::class, 'destroy']); // DELETE /api/devis/{id}
});

// Routes factures
// Routes factures pour les clients
Route::middleware(['auth:sanctum'])->prefix('factures')->group(function () {
    Route::get('/', [FactureController::class, 'index']);
    Route::get('/{id}', [FactureController::class, 'show']);
    Route::post('/generate', [FactureController::class, 'generate']);
    Route::post('/{id}/pay', [FactureController::class, 'markAsPaid']);
    Route::get('/{id}/download', [FactureController::class, 'download']);
});

// Routes admin pour les factures
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/factures', [FactureController::class, 'adminIndex']);
    Route::post('/factures', [FactureController::class, 'adminStore']);
    Route::get('/factures/{id}', [FactureController::class, 'adminShow']);
    Route::post('/factures/{id}/emettre', [FactureController::class, 'adminEmit']);
    Route::post('/factures/{id}/payer', [FactureController::class, 'adminMarkPaid']);
    Route::post('/factures/{id}/annuler', [FactureController::class, 'adminCancel']);
    Route::get('/factures/{id}/download', [FactureController::class, 'adminDownload']);
});

// Routes POUR la génération automatique de facture
Route::middleware(['auth:sanctum'])->prefix('factures')->group(function () {
    Route::get('/', [FactureController::class, 'index']);
    Route::get('/{id}', [FactureController::class, 'show']);
    Route::post('/generate', [FactureController::class, 'generate']);
    Route::post('/{id}/pay', [FactureController::class, 'markAsPaid']);
    Route::get('/{id}/download', [FactureController::class, 'download']);
    Route::delete('/{id}', [FactureController::class, 'destroy']);
});

// Routes admin pour les commandes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Commandes
    Route::get('/commandes', [CommandeController::class, 'adminIndex']);
    Route::patch('/commandes/{uuid}/statut', [CommandeController::class, 'adminUpdateStatus']);
    Route::post('/commandes/{uuid}/rembourser', [CommandeController::class, 'adminRembourser']);
    Route::delete('/commandes/{uuid}', [CommandeController::class, 'adminDestroy']);
});

// Routes admin pour les factures
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/factures', [FactureController::class, 'adminIndex']);
    Route::get('/factures/{id}', [FactureController::class, 'adminShow']);
    Route::post('/factures/{id}/emettre', [FactureController::class, 'adminEmit']);
    Route::post('/factures/{id}/payer', [FactureController::class, 'adminMarkPaid']);
    Route::post('/factures/{id}/annuler', [FactureController::class, 'adminCancel']);
    Route::delete('/factures/{id}', [FactureController::class, 'adminDestroy']);
    Route::get('/factures/{id}/download', [FactureController::class, 'adminDownload']);
});

 // Admin - Gestion des favoris
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/utilisateurs-avec-favoris', [AdminFavorisController::class, 'getUtilisateursAvecFavoris']);
    Route::get('/favoris/all', [AdminFavorisController::class, 'getAllFavoris']);
    Route::get('/favoris/stats', [AdminFavorisController::class, 'getStats']);
    Route::get('/utilisateurs/{userId}/favoris', [AdminFavorisController::class, 'getFavorisByUser']);
    Route::post('/favoris/envoyer-email', [AdminFavorisController::class, 'sendEmailFavoris']);
});

 // Admin - Gestion des paniers
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/utilisateurs-avec-paniers', [AdminPanierController::class, 'getUtilisateursAvecPaniers']);
    Route::get('/paniers/stats', [AdminPanierController::class, 'getStats']);
    Route::post('/paniers/envoyer-email', [AdminPanierController::class, 'sendEmailRappel']);
    Route::delete('/paniers/{id}', [AdminPanierController::class, 'deletePanier']);
});

// Admin - Gestion des boutiques Misa
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('boutique-misa', [BoutiqueMisaController::class, 'index']);
    Route::post('boutique-misa', [BoutiqueMisaController::class, 'store']);
    Route::get('boutique-misa/{id}', [BoutiqueMisaController::class, 'show']);
    Route::put('boutique-misa/{id}', [BoutiqueMisaController::class, 'update']);
    Route::delete('boutique-misa/{id}', [BoutiqueMisaController::class, 'destroy']);
    Route::patch('boutique-misa/{id}/stock', [BoutiqueMisaController::class, 'updateStock']);
});

// Routes pour les livreurs - TEMPORAIREMENT SANS AUTHENTIFICATION POUR TEST
Route::prefix('livreur-test')->group(function () {
    // Supprimer le middleware d'authentification pour le test
    Route::get('/commandes', [LivreurController::class, 'getCommandes']);
    Route::patch('/commandes/{uuid}/statut', [LivreurController::class, 'updateStatut']);
    Route::get('/commandes/{uuid}', [LivreurController::class, 'showCommande']);
});

// OU ALTERNATIVE : Garder l'auth mais permettre l'accès sans token
// Route::prefix('livreur')->middleware(['auth:sanctum'])->group(function () {
//     Route::get('/commandes', [LivreurController::class, 'getCommandes']);
//     Route::patch('/commandes/{uuid}/statut', [LivreurController::class, 'updateStatut']);
//     Route::get('/commandes/{uuid}', [LivreurController::class, 'showCommande']);
// });

// Route pour vérifier si un utilisateur est admin par son email
Route::middleware(['auth:sanctum'])->post('/admin/check-by-email', function (Request $request) {
    $email = $request->email;
    $isAdmin = Admin::where('email', $email)->exists();
    return response()->json(['isAdmin' => $isAdmin]);
});
