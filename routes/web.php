<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/image/{filename}', function (string $filename) {
    $safeFilename = basename($filename);
    $path = base_path('image' . DIRECTORY_SEPARATOR . $safeFilename);

    if (!File::exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->where('filename', '^[A-Za-z0-9._-]+$');

require __DIR__.'/auth.php';
