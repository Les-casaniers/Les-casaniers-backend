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
        Schema::create('attributs_produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->onDelete('cascade');
            $table->string('cle_attr', 80);
            $table->string('libelle_attr', 120)->nullable();
            $table->string('valeur_attr', 255);
            $table->timestamp('date_creation')->useCurrent();

            $table->index(['produit_id', 'cle_attr']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributs_produits');
    }
};
