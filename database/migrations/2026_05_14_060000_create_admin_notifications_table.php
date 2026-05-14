<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->index();          // commande, produit, client, facture, paiement, livraison, message, alerte
            $table->string('titre', 255);
            $table->text('message');
            $table->string('lien', 500)->nullable();       // Lien interne vers la ressource concernée
            $table->string('expediteur', 255)->nullable();  // Email ou nom de l'expéditeur si applicable
            $table->json('meta')->nullable();               // Données supplémentaires sérialisées
            $table->boolean('lue')->default(false)->index();
            $table->timestamp('date_creation')->useCurrent();
            $table->timestamp('date_lecture')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
