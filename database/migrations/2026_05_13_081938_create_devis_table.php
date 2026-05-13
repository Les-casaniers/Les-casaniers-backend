<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('utilisateur_id')
                ->nullable()
                ->constrained('utilisateurs')
                ->nullOnDelete();

            $table->foreignId('panier_id')
                ->nullable()
                ->constrained('paniers')
                ->nullOnDelete();

            $table->enum('statut', [
                'brouillon','envoye','accepte','refuse','expire'
            ])->default('brouillon');

            $table->text('note')->nullable();

            $table->decimal('montant_total', 12, 2)->default(0);
            $table->char('devise', 3)->default('MGA');

            $table->timestamp('date_creation')->useCurrent();
            $table->timestamp('date_modification')
                ->useCurrent()
                ->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devis');
    }
};