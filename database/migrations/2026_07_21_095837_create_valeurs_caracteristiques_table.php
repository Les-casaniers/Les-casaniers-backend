<?php
// database/migrations/2026_07_21_000002_create_valeurs_caracteristiques_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valeurs_caracteristiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')
                  ->constrained('produits')
                  ->onDelete('cascade');
            $table->foreignId('template_id')
                  ->constrained('templates_caracteristiques')
                  ->onDelete('cascade');
            $table->string('valeur', 500)->nullable();
            $table->timestamps();
            
            $table->unique(['produit_id', 'template_id']);
            $table->index('produit_id');
            $table->index('template_id');
            $table->index('valeur');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valeurs_caracteristiques');
    }
};