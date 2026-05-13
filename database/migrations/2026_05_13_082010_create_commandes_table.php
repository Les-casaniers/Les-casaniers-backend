<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();

            $table->uuid('commande_uuid');

            $table->foreignId('utilisateur_id')
                ->nullable()
                ->constrained('utilisateurs')
                ->nullOnDelete();

            $table->foreignId('panier_id')
                ->nullable()
                ->constrained('paniers')
                ->nullOnDelete();

            $table->foreignId('devis_id')
                ->nullable()
                ->constrained('devis')
                ->nullOnDelete();

            $table->enum('statut', [
                'en_attente','payee','en_traitement',
                'expediee','terminee','annulee','remboursee'
            ])->default('en_attente');

            $table->decimal('sous_total', 12, 2)->default(0);
            $table->decimal('livraison', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->char('devise', 3)->default('MGA');

            $table->foreignId('adresse_expedition_id')
                ->nullable()
                ->constrained('adresses_utilisateurs')
                ->nullOnDelete();

            $table->foreignId('adresse_facturation_id')
                ->nullable()
                ->constrained('adresses_utilisateurs')
                ->nullOnDelete();

            $table->foreignId('produit_id')
                ->nullable()
                ->constrained('produits')
                ->nullOnDelete();

            $table->string('titre');
            $table->string('reference')->nullable();

            $table->decimal('prix_unitaire', 12, 2)->default(0);
            $table->unsignedInteger('quantite')->default(1);

            $table->json('meta_json')->nullable();

            $table->timestamp('date_creation')->useCurrent();
            $table->timestamp('date_modification')
                ->useCurrent()
                ->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};