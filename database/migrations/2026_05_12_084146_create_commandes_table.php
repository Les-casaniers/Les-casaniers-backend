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
            $table->foreignId('utilisateur_id')
                ->constrained('utilisateurs')
                ->onDelete('cascade');
            $table->enum('statut', [
                'en_attente', 'payee', 'en_traitement', 
                'expediee', 'terminee', 'annulee', 'remboursee'
            ])->default('en_attente');
            $table->decimal('sous_total', 12, 2)->default(0);
            $table->decimal('livraison', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->char('devise', 3)->default('MGA');
            $table->text('adresse_livraison')->nullable();
            $table->text('adresse_facturation')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('date_creation')->nullable();
            $table->timestamp('date_modification')->nullable();
            $table->timestamps();
            
            $table->index('utilisateur_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};