<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Champs affichés sur la ligne produit du catalogue (maquette) :
 * EAN, usages (Gamer, Bureautique…) et specs principales.
 * Ils étaient jusqu'ici saisis comme caractéristiques ; on les déplace dans la table produits.
 */
return new class extends Migration
{
    /** colonne => nom de la caractéristique utilisé auparavant */
    private const FIELDS = [
        'ean' => 'EAN',
        'usages' => 'Usage',
        'processeur' => 'Processeur',
        'ssd' => 'SSD',
        'os' => 'OS',
        'gpu' => 'GPU',
        'resolution' => 'Résolution',
        'ram' => 'RAM',
        'taille' => 'Taille',
    ];

    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->string('ean', 32)->nullable()->after('reference');
            $table->string('usages')->nullable()->after('atout');
            $table->string('processeur')->nullable()->after('usages');
            $table->string('ssd')->nullable()->after('processeur');
            $table->string('os')->nullable()->after('ssd');
            $table->string('gpu')->nullable()->after('os');
            $table->string('resolution')->nullable()->after('gpu');
            $table->string('ram')->nullable()->after('resolution');
            $table->string('taille')->nullable()->after('ram');
        });

        // Reprise des valeurs déjà saisies comme caractéristiques, puis suppression des doublons
        foreach (self::FIELDS as $column => $nomChamp) {
            $templateIds = DB::table('templates_caracteristiques')
                ->where('nom_champ', $nomChamp)
                ->pluck('id');

            if ($templateIds->isEmpty()) {
                continue;
            }

            $valeurs = DB::table('valeurs_caracteristiques')
                ->whereIn('template_id', $templateIds)
                ->get(['produit_id', 'valeur']);

            foreach ($valeurs as $valeur) {
                DB::table('produits')
                    ->where('id', $valeur->produit_id)
                    ->whereNull($column)
                    ->update([$column => $valeur->valeur]);
            }

            DB::table('valeurs_caracteristiques')->whereIn('template_id', $templateIds)->delete();
            DB::table('templates_caracteristiques')->whereIn('id', $templateIds)->delete();
        }
    }

    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn(array_keys(self::FIELDS));
        });
    }
};
