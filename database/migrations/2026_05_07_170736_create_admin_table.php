<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin', function (Blueprint $table) {

            $table->id();

            $table->string('prenom', 100);
            $table->string('nom', 100);

            $table->string('email', 190)->unique();

            $table->string('telephone', 30)->nullable();

            $table->string('mot_de_passe');

            $table->enum('poste', [
                'admin',
                'support',
                'logistique'
            ])->default('admin');

            $table->enum('statut', [
                'actif',
                'desactive'
            ])->default('actif');

            $table->timestamp('date_creation')->useCurrent();

            $table->timestamp('date_modification')
                ->useCurrent()
                ->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin');
    }
};