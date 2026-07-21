<?php
// database/migrations/2026_07_21_000001_create_templates_caracteristiques_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates_caracteristiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sous_categorie_id')
                  ->constrained('sous_categories')
                  ->onDelete('cascade');
            $table->string('nom_champ', 100);
            $table->enum('type_champ', ['texte', 'nombre', 'booleen', 'date'])
                  ->default('texte');
            $table->integer('ordre_affichage')->default(0);
            $table->boolean('est_obligatoire')->default(false);
            $table->string('valeur_par_defaut', 255)->nullable();
            $table->timestamps();
            
            $table->unique(['sous_categorie_id', 'nom_champ']);
            $table->index('sous_categorie_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates_caracteristiques');
    }
};