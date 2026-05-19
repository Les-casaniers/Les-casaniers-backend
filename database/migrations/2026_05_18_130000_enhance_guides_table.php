<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guides', function (Blueprint $table) {
            $table->string('slug', 255)->nullable()->unique()->after('titre');
            $table->string('badge', 60)->nullable()->after('categorie');
            $table->decimal('budget_min', 14, 2)->nullable()->after('badge');
            $table->decimal('budget_max', 14, 2)->nullable()->after('budget_min');
            $table->json('composants_recommandes')->nullable()->after('budget_max');
            $table->string('niveau', 40)->nullable()->after('composants_recommandes');
            $table->string('difficulte', 40)->nullable()->after('niveau');
            $table->string('duree', 60)->nullable()->after('difficulte');
            $table->json('etapes')->nullable()->after('duree');
            $table->string('video_url', 1000)->nullable()->after('etapes');
            $table->json('tags')->nullable()->after('video_url');
            $table->unsignedInteger('ordre')->default(0)->after('tags');
            $table->boolean('mis_en_avant')->default(false)->after('ordre');
        });
    }

    public function down(): void
    {
        Schema::table('guides', function (Blueprint $table) {
            $table->dropUnique('guides_slug_unique');
            $table->dropColumn([
                'slug', 'badge', 'budget_min', 'budget_max',
                'composants_recommandes', 'niveau', 'difficulte',
                'duree', 'etapes', 'video_url', 'tags', 'ordre', 'mis_en_avant',
            ]);
        });
    }
};
