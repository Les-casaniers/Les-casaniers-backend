<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', [UserController::class, 'user']);
use App\Http\Controllers\Api\AdminAuthController;

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

use App\Http\Controllers\Api\UtilisateurController;

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

Route::post('/change-password', [UtilisateurController::class, 'changePasswordByEmail']);

use App\Http\Controllers\Api\Produits\CategoryController;
use App\Http\Controllers\Api\Produits\ProduitController;
use App\Http\Controllers\Api\Produits\ImageProduitController;
use App\Http\Controllers\Api\Produits\AttributProduitController;

// Routes Publiques (Front-end)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/produits', [ProduitController::class, 'index']);
Route::get('/produits/{id}', [ProduitController::class, 'show']);

// Routes ProtÃ©gÃ©es (Back-office / Admin)
Route::middleware(['auth:sanctum'])->group(function () {
    // CatÃ©gories
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::patch('/categories/reorder', [CategoryController::class, 'reorder']);

    // Produits
    Route::post('/produits', [ProduitController::class, 'store']);
    Route::put('/produits/{id}', [ProduitController::class, 'update']);
    Route::delete('/produits/{id}', [ProduitController::class, 'destroy']);
    Route::patch('/produits/{id}/toggle-status', [ProduitController::class, 'toggleStatus']);

    // Images
    Route::post('/produits/{produitId}/images', [ImageProduitController::class, 'store']);
    Route::delete('/images/{id}', [ImageProduitController::class, 'destroy']);
    Route::patch('/produits/{produitId}/images/{imageId}/set-main', [ImageProduitController::class, 'setMain']);

    // Attributs
    Route::post('/produits/{produitId}/attributes/sync', [AttributProduitController::class, 'sync']);
    Route::get('/attributes/standard-keys', [AttributProduitController::class, 'getStandardKeys']);
});
