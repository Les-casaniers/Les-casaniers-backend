<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('guides') && Schema::hasColumn('guides', 'slug')) {
            Schema::table('guides', function (Blueprint $table) {
                $table->dropUnique('guides_slug_unique');
                $table->dropColumn('slug');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('guides') && !Schema::hasColumn('guides', 'slug')) {
            Schema::table('guides', function (Blueprint $table) {
                $table->string('slug', 255)->nullable()->unique()->after('titre');
            });
        }
    }
};
