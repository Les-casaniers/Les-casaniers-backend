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
            $table->foreignId('utilisateur_id')->nullable()->constrained('utilisateurs')->onDelete('set null');
            $table->enum('statut', ['actif', 'commande', 'abandonne'])->default('actif');
            $table->timestamp('date_creation')->nullable()->useCurrent();
            $table->timestamp('date_modification')->nullable()->useCurrentOnUpdate();
            $table->timestamps();
            
            $table->index('utilisateur_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paniers');
    }
};