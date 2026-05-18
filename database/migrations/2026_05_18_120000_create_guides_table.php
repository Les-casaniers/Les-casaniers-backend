<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guides', function (Blueprint $table) {
            $table->id();
            $table->string('titre', 255);
            $table->string('resume', 1000);
            $table->longText('contenu');
            $table->enum('categorie', ['guides-achat', 'actualites-tech', 'tutos-maintenance'])->index();
            $table->enum('statut', ['publie', 'brouillon', 'archive'])->default('brouillon')->index();
            $table->string('image_url', 1000)->nullable();
            $table->string('image_alt', 255)->nullable();
            $table->string('auteur', 120)->nullable();
            $table->string('temps_lecture', 40)->nullable();
            $table->unsignedInteger('popularite')->default(0);
            $table->unsignedInteger('vues')->default(0);
            $table->timestamp('publie_le')->nullable()->index();
            $table->timestamp('date_creation')->useCurrent();
            $table->timestamp('date_modification')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};
