<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('factures')) {
            Schema::create('factures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('commande_id')->unique()->constrained('commandes')->cascadeOnDelete();
                $table->string('facture_ref', 30)->unique();
                $table->enum('statut', ['brouillon', 'emise', 'payee', 'annulee'])->default('brouillon');
                $table->decimal('montant_total', 12, 2)->default(0);
                $table->char('devise', 3)->default('MGA');
                $table->string('methode_paiement', 80)->nullable();
                $table->timestamp('date_emission')->nullable();
                $table->timestamp('date_paiement')->nullable();
                $table->string('pdf_path')->nullable();
                $table->timestamp('date_creation')->useCurrent();
                $table->timestamp('date_modification')->useCurrent()->useCurrentOnUpdate();

                $table->index('statut');
                $table->index('date_emission');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
