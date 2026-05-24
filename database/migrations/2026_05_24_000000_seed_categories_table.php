<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('categories')->insert([
            [
                'nom' => 'Boitier',
                'code' => 'CASE-',
                'parent_id' => null,
                'date_modification' => now(),
            ],
            [
                'nom' => 'Processeur (CPU)',
                'code' => 'CPU-',
                'parent_id' => null,
                'date_modification' => now(),
            ],
            [
                'nom' => 'Carte mere',
                'code' => 'MB-',
                'parent_id' => null,
                'date_modification' => now(),
            ],
            [
                'nom' => 'Refroidissement',
                'code' => 'CL-',
                'parent_id' => null,
                'date_modification' => now(),
            ],
            [
                'nom' => 'Memoire RAM',
                'code' => 'RAM-',
                'parent_id' => null,
                'date_modification' => now(),
            ],
            [
                'nom' => 'SSD',
                'code' => 'SD-',
                'parent_id' => null,
                'date_modification' => now(),
            ],
            [
                'nom' => 'HDD',
                'code' => 'HDD-',
                'parent_id' => null,
                'date_modification' => now(),
            ],
            [
                'nom' => 'Carte graphique (GPU)',
                'code' => 'GPU-',
                'parent_id' => null,
                'date_modification' => now(),
            ],
            [
                'nom' => 'Alimentation',
                'code' => 'PSU-',
                'parent_id' => null,
                'date_modification' => now(),
            ],
            [
                'nom' => 'PC Portable',
                'code' => 'LAPTOP-',
                'parent_id' => null,
                'date_modification' => now(),
            ],
            [
                'nom' => 'PC Bureau',
                'code' => 'DESKTOP-',
                'parent_id' => null,
                'date_modification' => now(),
            ],
            [
                'nom' => 'PC Gamer',
                'code' => 'GAME-',
                'parent_id' => null,
                'date_modification' => now(),
            ],
            [
                'nom' => 'Stockage SSD',
                'code' => 'SSD-',
                'parent_id' => null,
                'date_modification' => now(),
            ],
            [
                'nom' => 'Stockage HDD',
                'code' => 'HDD-',
                'parent_id' => null,
                'date_modification' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('categories')->whereIn('code', [
            'CASE-',
            'CPU-',
            'MB-',
            'CL-',
            'RAM-',
            'SD-',
            'HDD-',
            'GPU-',
            'PSU-',
            'LAPTOP-',
            'DESKTOP-',
            'GAME-',
            'SSD-',
        ])->delete();
    }
};
