<?php
// database/migrations/xxxx_add_boutique_id_to_paniers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paniers', function (Blueprint $table) {
            // ✅ Ajouter boutique_id (nullable)
            $table->foreignId('boutique_id')->nullable()->after('produit_id')
                  ->constrained('boutique_misa')->onDelete('set null');
            
            // ✅ Rendre produit_id nullable
            $table->foreignId('produit_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('paniers', function (Blueprint $table) {
            $table->dropForeign(['boutique_id']);
            $table->dropColumn('boutique_id');
            $table->foreignId('produit_id')->nullable(false)->change();
        });
    }
};