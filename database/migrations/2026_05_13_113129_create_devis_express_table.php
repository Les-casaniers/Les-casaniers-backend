<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devis_express', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 190);
            $table->string('email', 190);
            $table->string('telephone', 30);
            $table->string('entreprise', 190)->nullable();
            $table->string('besoin', 255);
            $table->string('budget', 100)->nullable();
            $table->date('date_souhaitee')->nullable();
            $table->text('message')->nullable();
            $table->enum('statut', ['en_attente', 'traite', 'repondu', 'archive'])->default('en_attente');
            $table->timestamp('date_creation')->nullable()->useCurrent();
            $table->timestamp('date_modification')->nullable()->useCurrentOnUpdate();
            $table->timestamps();
            
            $table->index('email');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devis_express');
    }
};
