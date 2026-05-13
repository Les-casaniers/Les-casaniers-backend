<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis_clients', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('produit_id')
                ->constrained('produits')
                ->cascadeOnDelete();

            $table->foreignId('utilisateur_id')
                ->nullable()
                ->constrained('utilisateurs')
                ->nullOnDelete();

            // Avis
            $table->unsignedTinyInteger('note');
            $table->text('corps')->nullable();

            // Publication (0 = en attente, 1 = publié)
            $table->boolean('publie')->default(false);

            // Date création custom
            $table->timestamp('date_creation')->useCurrent();

            // Pas de update automatique
            $table->timestamp('date_modification')->nullable();

            // Index
            $table->index('produit_id');
            $table->index('utilisateur_id');
            $table->index('publie');

            // Optionnel : éviter doublon avis utilisateur sur produit
            $table->unique(['produit_id', 'utilisateur_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis_clients');
    }
};