<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AvisClients\AvisClientController;
use App\Http\Controllers\Api\Favoris\FavorisController;
use App\Http\Controllers\Api\Paniers\PanierController;
use App\Http\Controllers\Api\Produits\AttributProduitController;
use App\Http\Controllers\Api\Produits\CategoryController;
use App\Http\Controllers\Api\Produits\ConfigurationController;
use App\Http\Controllers\Api\Produits\ImageProduitController;
use App\Http\Controllers\Api\Produits\ProduitController;
use App\Http\Controllers\Api\Sales\CommandeController;
use App\Http\Controllers\Api\Sales\DevisController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UtilisateurController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use App\Http\Controllers\Api\DevisExpress\DevisExpressController;

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
Route::get('/produits', [ProduitController::class, 'index']);
Route::get('/produits/{id}', [ProduitController::class, 'show']);
Route::get('/produits/{produitId}/avis', [AvisClientController::class, 'getAvisByProduit']);
Route::get('/produits/{produitId}/avis/stats', [AvisClientController::class, 'getStatistiquesProduit']);
Route::get('/avis/latest', [AvisClientController::class, 'latest']);

// Protected product/admin routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::patch('/categories/reorder', [CategoryController::class, 'reorder']);

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
Route::middleware(['auth:sanctum'])->prefix('devis')->group(function () {
    Route::get('/', [DevisController::class, 'index']);              // Mes devis
    Route::post('/creer', [DevisController::class, 'creer']);        // Créer un devis
    Route::get('/{id}', [DevisController::class, 'show']);           // Voir un devis
    Route::put('/{id}/envoyer', [DevisController::class, 'envoyer']); // Envoyer un devis
    Route::delete('/{id}', [DevisController::class, 'destroy']);     // Supprimer un devis
});

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

use App\Http\Controllers\Api\Adresse\AdresseController;

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



// Route publique pour soumettre un devis express
Route::post('/devis-express', [DevisExpressController::class, 'store']);

// Routes admin (protégées)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/devis-express', [DevisExpressController::class, 'adminList']);
    Route::get('/devis-express/{id}', [DevisExpressController::class, 'adminShow']);
    Route::put('/devis-express/{id}/statut', [DevisExpressController::class, 'adminUpdateStatut']);
    Route::delete('/devis-express/{id}', [DevisExpressController::class, 'adminDestroy']);
});