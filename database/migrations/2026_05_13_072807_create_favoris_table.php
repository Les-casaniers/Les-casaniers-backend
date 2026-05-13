<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favoris', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('utilisateur_id')
                ->constrained('utilisateurs')
                ->onDelete('cascade');

            $table->foreignId('produit_id')
                ->constrained('produits')
                ->onDelete('cascade');

            // Date création
            $table->timestamp('date_creation')
                ->useCurrent();

            // Empêche doublon utilisateur + produit
            $table->unique(['utilisateur_id', 'produit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favoris');
    }
};