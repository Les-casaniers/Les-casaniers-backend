<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adresses_utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->string('etiquette', 50)->nullable();
            $table->string('nom_complet', 190);
            $table->string('telephone', 30)->nullable();
            $table->string('adresse_ligne1', 190);
            $table->string('adresse_ligne2', 190)->nullable();
            $table->string('ville', 120);
            $table->string('region', 120)->nullable();
            $table->string('code_postal', 20)->nullable();
            $table->string('pays', 80)->default('Madagascar');
            $table->boolean('par_defaut_expedition')->default(false);
            $table->boolean('par_defaut_facturation')->default(false);
            $table->timestamp('date_creation')->nullable()->useCurrent();
            $table->timestamp('date_modification')->nullable()->useCurrentOnUpdate();
            $table->timestamps();
            
            $table->index('utilisateur_id');
            $table->index('par_defaut_expedition');
            $table->index('par_defaut_facturation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adresses_utilisateurs');
    }
};
