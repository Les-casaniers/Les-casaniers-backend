<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->string('prenom', 100);
            $table->string('nom', 100);
            $table->string('email', 190)->unique();
            $table->string('telephone', 30)->nullable();
            $table->string('mot_de_passe', 255);
            $table->enum('statut', ['actif', 'desactive'])->default('actif');
            $table->timestamp('date_creation')->nullable()->useCurrent();
            $table->timestamp('date_modification')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utilisateurs');
    }
};
