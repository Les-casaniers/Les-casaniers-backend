<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items_configuration', function (Blueprint $table) {
            $table->id();
            $table->foreignId('configuration_id')->constrained('configurations')->onDelete('cascade');
            $table->unsignedBigInteger('etape_id')->nullable(); 
            $table->string('emplacement', 80); // ex: cpu, carte_mere, gpu, ram, alim, refroidissement, stockage
            $table->foreignId('produit_id')->nullable()->constrained('produits')->onDelete('set null');
            $table->string('titre', 255);
            $table->unsignedInteger('quantite')->default(1);
            $table->decimal('prix_unitaire', 12, 2)->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamp('date_creation')->nullable()->useCurrent();
            $table->timestamp('date_modification')->nullable()->useCurrentOnUpdate();
            $table->timestamps();
            
            $table->index(['configuration_id', 'emplacement'], 'idx_configuration_emplacement');
            $table->index('etape_id');
            $table->index('produit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_configuration');
    }
};