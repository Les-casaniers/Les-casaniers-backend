<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->nullable()->constrained('utilisateurs')->onDelete('set null');
            $table->foreignId('profil_id')->constrained('profils_configurateur')->onDelete('cascade');
            $table->string('nom', 190)->nullable();
            $table->enum('statut', ['brouillon', 'pret', 'devis', 'commande'])->default('brouillon');
            $table->decimal('prix_total', 12, 2)->nullable();
            $table->char('devise', 3)->default('MGA');
            $table->timestamp('date_creation')->nullable()->useCurrent();
            $table->timestamp('date_modification')->nullable()->useCurrentOnUpdate();
            $table->timestamps();
            
            $table->index('utilisateur_id');
            $table->index('statut');
            $table->index('profil_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};