<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test', function (Blueprint $table) {
            $table->id();                           // Colonne id auto-incrémentée
            $table->string('nom_test', 255);        // Colonne nom_test (VARCHAR)
            $table->text('desc_test');               // Colonne desc_test (TEXT)
            $table->timestamps();                   // Colonnes created_at et updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test');
    }
};