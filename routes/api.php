<?php

use App\Http\Controllers\Api\Adresse\AdresseController;
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
use App\Http\Controllers\Api\Sales\FactureController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UtilisateurController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

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

Route::middleware(['auth:sanctum'])->prefix('factures')->group(function () {
    Route::get('/', [FactureController::class, 'index']);
    Route::get('/{id}', [FactureController::class, 'show']);
    Route::get('/{id}/download', [FactureController::class, 'download']);
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
});

// Panier routes
Route::middleware(['auth:sanctum'])->prefix('panier')->group(function () {
    Route::get('/', [PanierController::class, 'index']);
    Route::post('/ajouter', [PanierController::class, 'ajouter']);
    Route::put('/modifier/{itemId}', [PanierController::class, 'modifierQuantite']);
    Route::delete('/supprimer/{itemId}', [PanierController::class, 'supprimer']);
    Route::delete('/vider', [PanierController::class, 'vider']);
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
    Route::put('/{id}/defaut-expedition', [AdresseController::class, 'setDefaultExpedition']);
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('adresses')->group(function () {
    Route::delete('/{id}', [AdresseController::class, 'destroy']);
});
