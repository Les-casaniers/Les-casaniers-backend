<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        
        Schema::create('devis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->nullable()->constrained('utilisateurs')->onDelete('set null');
            $table->foreignId('panier_id')->nullable()->constrained('paniers')->onDelete('set null');
            $table->unsignedBigInteger('configuration_id')->nullable(); // ← Modifier ici
            // $table->foreignId('configuration_id')->nullable()->constrained('configurations')->onDelete('set null');
            $table->enum('statut', ['brouillon', 'envoye', 'accepte', 'refuse', 'expire'])->default('brouillon');
            $table->string('nom_client', 190);
            $table->string('email_client', 190);
            $table->string('telephone_client', 30)->nullable();
            $table->text('note')->nullable();
            $table->decimal('montant_total', 12, 2)->nullable();
            $table->char('devise', 3)->default('MGA');
            $table->timestamp('date_creation')->nullable()->useCurrent();
            $table->timestamp('date_modification')->nullable()->useCurrentOnUpdate();
            $table->timestamps();
            
            $table->index('configuration_id');
        });
        
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('devis');
    }
};