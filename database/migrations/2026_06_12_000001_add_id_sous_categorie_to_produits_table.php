<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('produits') || Schema::hasColumn('produits', 'id_sous_categorie')) {
            return;
        }

        Schema::table('produits', function (Blueprint $table) {
            $table->foreignId('id_sous_categorie')->nullable()->after('categorie_id')
                ->constrained('sous_categories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('produits') || !Schema::hasColumn('produits', 'id_sous_categorie')) {
            return;
        }

        Schema::table('produits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_sous_categorie');
        });
    }
};
