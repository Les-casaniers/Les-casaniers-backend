<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profils_configurateur', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 190);
            $table->string('slug', 190)->unique();
            $table->text('description')->nullable();
            $table->json('emplacements')->nullable(); // Liste des emplacements requis
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profils_configurateur');
    }
};
