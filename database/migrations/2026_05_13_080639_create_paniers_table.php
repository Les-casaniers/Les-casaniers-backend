<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paniers', function (Blueprint $table) {
            $table->id();

            // Utilisateur (nullable = invité)
            $table->foreignId('utilisateur_id')
                ->nullable()
                ->constrained('utilisateurs')
                ->nullOnDelete();

            // Statut du panier
            $table->enum('statut', ['actif', 'commande', 'abandonne'])
                ->default('actif');

            // Produits ou configuration PC
            $table->foreignId('produit_id')
                ->nullable()
                ->constrained('produits')
                ->nullOnDelete();

            // $table->foreignId('configuration_id')
            //     ->nullable()
            //     ->constrained('configurations_pc')
            //     ->nullOnDelete();

            $table->foreignId('configuration_id')
                    ->nullable()
                    ->constrained('configurations')
                    ->nullOnDelete();

            // Données item panier
            $table->string('titre');
            $table->decimal('prix_unitaire', 12, 2)->nullable();
            $table->unsignedInteger('quantite')->default(1);

            // Dates
            $table->timestamp('date_creation')->useCurrent();
            $table->timestamp('date_modification')->useCurrent()->useCurrentOnUpdate();

            // Empêche doublons panier
            $table->unique([
                'utilisateur_id',
                'statut',
                'produit_id',
                'configuration_id'
            ], 'uq_panier_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paniers');
    }
};