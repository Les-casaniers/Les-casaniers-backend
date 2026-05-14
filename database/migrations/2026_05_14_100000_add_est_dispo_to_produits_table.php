<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->boolean('est_dispo')->default(true)->after('quantite_stock');
        });

        DB::table('produits')->update([
            'est_dispo' => DB::raw('CASE WHEN COALESCE(quantite_stock, 0) > 0 THEN 1 ELSE 0 END'),
        ]);
    }

    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn('est_dispo');
        });
    }
};
