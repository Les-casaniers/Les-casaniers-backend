<?php
// database/migrations/2026_01_15_000000_create_boutique_misa_table.php

// use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;

// return new class extends Migration
// {
//     public function up(): void
//     {
//         Schema::create('boutique_misa', function (Blueprint $table) {
//             $table->id();
//             $table->string('nom');
//             $table->text('description')->nullable();
//             $table->integer('stock')->default(0);
//             $table->decimal('prix', 10, 2);
//             $table->string('image_url')->nullable(); 
//             $table->timestamps();
//         });
//     }

//     public function down(): void
//     {
//         Schema::dropIfExists('boutique_misa');
//     }
// };

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boutique_misa', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->integer('stock')->default(0);
            $table->decimal('prix', 10, 2);
            $table->string('image_url')->nullable(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boutique_misa');
    }
};
