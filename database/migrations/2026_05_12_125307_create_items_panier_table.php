<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items_panier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panier_id')->constrained('paniers')->onDelete('cascade');
            $table->foreignId('produit_id')->nullable()->constrained('produits')->onDelete('set null');
            $table->unsignedBigInteger('configuration_id')->nullable();
            $table->string('titre', 255);
            $table->decimal('prix_unitaire', 12, 2)->nullable();
            $table->unsignedInteger('quantite')->default(1);
            $table->timestamp('date_creation')->nullable()->useCurrent();
            $table->timestamp('date_modification')->nullable()->useCurrentOnUpdate();
            $table->timestamps();
            
            $table->index('panier_id');
            $table->index('produit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_panier');
    }
};