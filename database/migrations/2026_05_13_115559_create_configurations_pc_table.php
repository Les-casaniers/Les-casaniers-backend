<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configurations_pc', function (Blueprint $table) {
            $table->id();

            $table->foreignId('produit_id')
                ->nullable()
                ->constrained('produits')
                ->nullOnDelete();

            $table->foreignId('utilisateur_id')
                ->nullable()
                ->constrained('utilisateurs')
                ->nullOnDelete();

            $table->decimal('prix_total', 12, 2)->default(0);

            $table->json('composants_json')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configurations_pc');
    }
};