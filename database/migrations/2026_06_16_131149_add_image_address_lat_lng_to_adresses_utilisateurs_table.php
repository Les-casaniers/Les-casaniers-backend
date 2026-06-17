<?php
// database/migrations/2026_01_XX_add_image_address_lat_lng_to_adresses_utilisateurs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adresses_utilisateurs', function (Blueprint $table) {
            $table->string('image_adress')->nullable()->after('pays');
            $table->decimal('latitude', 10, 8)->nullable()->after('image_adress');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('adresses_utilisateurs', function (Blueprint $table) {
            $table->dropColumn(['image_adress', 'latitude', 'longitude']);
        });
    }
};
