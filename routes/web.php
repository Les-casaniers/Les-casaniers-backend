<?php

use App\Http\Controllers\UtilisateurController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the RouteServiceProvider within a group
| which contains the "web" middleware group. Now create something great!
|
*/

// ============================================
// ROUTE POUR SERVIR LES IMAGES
// ============================================
Route::get('/image/{filename}', function ($filename) {
    $filename = basename($filename);
    
    // 1. Vérifier dans public/image/ (principal)
    $path = public_path('image/' . $filename);
    
    if (!file_exists($path)) {
        // 2. Vérifier dans base_path('image/')
        $path = base_path('image/' . $filename);
    }
    
    if (!file_exists($path)) {
        // 3. Vérifier dans storage/app/public/image/
        $path = storage_path('app/public/image/' . $filename);
    }
    
    if (!file_exists($path)) {
        // 4. Vérifier dans storage/app/public/images/
        $path = storage_path('app/public/images/' . $filename);
    }
    
    if (!file_exists($path)) {
        // 5. Vérifier dans public/images/
        $path = public_path('images/' . $filename);
    }
    
    if (!file_exists($path)) {
        // 6. Vérifier dans storage/app/public/uploads/image/
        $path = storage_path('app/public/uploads/image/' . $filename);
    }
    
    if (!file_exists($path)) {
        // Image non trouvée
        return response()->json([
            'success' => false,
            'error' => 'Image not found',
            'filename' => $filename
        ], 404);
    }
    
    // Déterminer le type MIME
    $mime = mime_content_type($path);
    
    // Retourner l'image
    return response()->file($path, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=31536000',
        'Pragma' => 'cache',
        'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
    ]);
})->name('image.show');

// Route pour servir les images du storage (fallback)
Route::get('/storage/image/{filename}', function ($filename) {
    $filename = basename($filename);
    
    $paths = [
        storage_path('app/public/image/' . $filename),
        storage_path('app/public/images/' . $filename),
        storage_path('app/public/uploads/image/' . $filename),
        storage_path('app/public/uploads/images/' . $filename),
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            $mime = mime_content_type($path);
            return response()->file($path, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }
    }
    
    return response()->json(['error' => 'Image not found'], 404);
})->name('storage.image.show');

// ============================================
// ROUTE PAR DEFAUT
// ============================================
Route::get('/', function () {
    return view('welcome');
});

// ============================================
// ROUTES UTILISATEURS (WEB)
// ============================================
Route::prefix('utilisateurs')->group(function () {
    Route::get('/', [UtilisateurController::class, 'index'])->name('utilisateurs.index');
    Route::get('/search', [UtilisateurController::class, 'search'])->name('utilisateurs.search');
    Route::get('/{id}', [UtilisateurController::class, 'show'])->name('utilisateurs.show');
    Route::post('/', [UtilisateurController::class, 'store'])->name('utilisateurs.store');
    Route::put('/{id}', [UtilisateurController::class, 'update'])->name('utilisateurs.update');
    Route::delete('/{id}', [UtilisateurController::class, 'destroy'])->name('utilisateurs.destroy');
});

// ============================================
// ROUTES API POUR LES UTILISATEURS (WEB)
// ============================================
Route::prefix('api')->group(function () {
    Route::get('/utilisateurs', [UtilisateurController::class, 'getUsers']);
    Route::get('/utilisateurs/{id}', [UtilisateurController::class, 'getUser']);
    Route::post('/utilisateurs', [UtilisateurController::class, 'apiStore']);
    Route::put('/utilisateurs/{id}', [UtilisateurController::class, 'apiUpdate']);
    Route::delete('/utilisateurs/{id}', [UtilisateurController::class, 'apiDestroy']);
    Route::post('/utilisateurs/bulk/activate', [UtilisateurController::class, 'bulkActivate']);
    Route::post('/utilisateurs/bulk/delete', [UtilisateurController::class, 'bulkDelete']);
    Route::get('/utilisateurs/export/csv', [UtilisateurController::class, 'exportCsv']);
});

// ============================================
// ROUTE DE TEST POUR LES IMAGES
// ============================================
Route::get('/test-image', function () {
    $baseUrl = config('app.url');
    return response()->json([
        'base_url' => $baseUrl,
        'image_url' => $baseUrl . '/image/test.jpg',
        'public_path' => public_path('image'),
        'image_exists' => file_exists(public_path('image/test.jpg')),
        'base_path' => base_path('image'),
        'base_image_exists' => file_exists(base_path('image/test.jpg')),
    ]);
});