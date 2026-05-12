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
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')->constrained('categories');
            $table->string('reference', 80)->nullable()->unique();
            $table->string('slug', 190)->unique();
            $table->string('nom', 255);
            $table->string('description_courte', 1000)->nullable();
            $table->longText('description')->nullable();
            $table->enum('type_produit', ['pc', 'portable', 'composant', 'peripherique', 'service']);
            $table->decimal('prix', 12, 2)->nullable();
            $table->char('devise', 3)->default('MGA');
            $table->integer('quantite_stock')->nullable();
            $table->boolean('actif')->default(1);
            $table->timestamp('date_creation')->useCurrent();
            $table->timestamp('date_modification')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
