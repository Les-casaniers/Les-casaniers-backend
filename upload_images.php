<?php
// upload_images.php
// Exécuter: php upload_images.php

require __DIR__ . '/vendor/autoload.php';

use App\Models\Produit;
use App\Models\ImageProduit;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

// Initialiser Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "========================================\n";
echo "     UPLOAD DES IMAGES PRODUITS        \n";
echo "========================================\n\n";

// Créer les dossiers nécessaires
$directories = [
    public_path('image'),
    base_path('image'),
    storage_path('app/public/image'),
    storage_path('app/public/images'),
];

foreach ($directories as $dir) {
    if (!File::exists($dir)) {
        File::makeDirectory($dir, 0755, true);
        echo "✅ Dossier créé: " . $dir . "\n";
    }
}

// URL de base
$baseUrl = config('app.url');

// Récupérer tous les produits
$produits = Produit::with('images')->get();

echo "\n📦 Produits trouvés: " . $produits->count() . "\n";
echo "----------------------------------------\n\n";

$uploaded = 0;
$alreadyHave = 0;
$errors = 0;

foreach ($produits as $produit) {
    // Vérifier si le produit a déjà des images
    if ($produit->images->count() > 0) {
        echo "⏭️  Produit #{$produit->id} - {$produit->nom}: déjà {$produit->images->count()} image(s)\n";
        $alreadyHave++;
        continue;
    }
    
    // Générer un nom unique
    $filename = Str::uuid() . '.jpg';
    
    // Essayer plusieurs sources d'images
    $imageData = null;
    
    // 1. Essayer picsum.photos
    try {
        $imageData = @file_get_contents('https://picsum.photos/seed/' . $produit->id . '/300/300');
    } catch (Exception $e) {
        // Ignorer
    }
    
    // 2. Fallback: via.placeholder.com
    if ($imageData === false) {
        try {
            $text = urlencode(substr($produit->nom, 0, 20));
            $imageData = @file_get_contents("https://via.placeholder.com/300x300/4A90D9/FFFFFF?text={$text}");
        } catch (Exception $e) {
            // Ignorer
        }
    }
    
    // 3. Fallback: créer une image simple avec GD si disponible
    if ($imageData === false && function_exists('imagecreate')) {
        $img = imagecreate(300, 300);
        $bg = imagecolorallocate($img, 74, 144, 217);
        $textColor = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, 300, 300, $bg);
        imagestring($img, 5, 50, 140, substr($produit->nom, 0, 15), $textColor);
        ob_start();
        imagejpeg($img, null, 80);
        $imageData = ob_get_clean();
        imagedestroy($img);
    }
    
    if ($imageData === false) {
        echo "❌ Erreur: impossible de créer l'image pour #{$produit->id} - {$produit->nom}\n";
        $errors++;
        continue;
    }
    
    // Sauvegarder l'image
    $targetPath = public_path('image/' . $filename);
    file_put_contents($targetPath, $imageData);
    
    // Créer l'entrée en base de données
    try {
        ImageProduit::create([
            'produit_id' => $produit->id,
            'url' => $baseUrl . '/image/' . $filename,
            'alt' => $produit->nom,
            'ordre' => 0,
        ]);
        
        echo "✅ Produit #{$produit->id} - {$produit->nom}: image ajoutée\n";
        $uploaded++;
    } catch (Exception $e) {
        echo "❌ Erreur DB pour #{$produit->id}: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n========================================\n";
echo "              RÉSUMÉ                    \n";
echo "========================================\n";
echo "📸 Images uploadées: " . $uploaded . "\n";
echo "⏭️  Produits déjà avec images: " . $alreadyHave . "\n";
echo "❌ Erreurs: " . $errors . "\n";
echo "📁 Dossier images: public/image/\n";
echo "✅ Terminé !\n";