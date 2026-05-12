<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->onDelete('cascade');
            $table->foreignId('utilisateur_id')->nullable()->constrained('utilisateurs')->onDelete('set null');
            $table->tinyInteger('note')->unsigned();
            $table->string('titre', 190)->nullable();
            $table->text('corps')->nullable();
            $table->boolean('publie')->default(false);
            $table->timestamp('date_creation')->nullable()->useCurrent();
            
            $table->index('produit_id');
            $table->index('utilisateur_id');
            $table->index('publie');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis_clients');
    }
};
