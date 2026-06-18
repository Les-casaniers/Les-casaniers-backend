<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('admin')->insertOrIgnore([
            [
                'prenom'            => 'Admin',
                'nom'               => 'Les casaniers',
                'email'             => 'contact@lc-cie.com',
                'telephone'         => '0385157042',
                'mot_de_passe'      => Hash::make('AdminCasaniers2026-'),
                'poste'             => 'admin',
                'statut'            => 'actif',
                'date_creation'     => now(),
                'date_modification' => now(),
            ],
            [
                'prenom'            => 'Jeanne',
                'nom'               => 'Livreur',
                'email'             => 'livreur@lc-cie.com',
                'telephone'         => '0341234567',
                'mot_de_passe'      => Hash::make('Livreur2026-'),
                'poste'             => 'livreur',
                'statut'            => 'actif',
                'date_creation'     => now(),
                'date_modification' => now(),
            ],
        ]);
    }
}
