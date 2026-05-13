<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("configurations", function (Blueprint $table) {
            $table->id();

            // Produit lié
            $table->foreignId("produit_id")
                ->constrained("produits")
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // Utilisateur (nullable = invité)
            $table->foreignId("utilisateur_id")
                ->nullable()
                ->constrained("utilisateurs")
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // Type de configuration
            $table->enum("nom_configuration", [
                "cpu","carte_mere","gpu","ram","ssd","hdd","stockage",
                "alimentation","boitier","refroidissement","ventilateur",
                "ecran","clavier","souris","os","reseau","autre"
            ]);

            // Champ libre si "autre"
            $table->string("nom_configuration_autre", 190)->nullable();

            $table->char("devise", 3)->default("MGA");

            // Prix total
            $table->decimal("prix_total", 12, 2)->default(0);

            // Composants JSON
            $table->json("composants_json");

            // Dates custom
            $table->timestamp("date_creation")->useCurrent();
            $table->timestamp("date_modification")
                ->useCurrent()
                ->useCurrentOnUpdate();

            // Index
            $table->index("produit_id");
            $table->index("utilisateur_id");

            // Contraintes logiques (CHECK) - Laravel does not directly support check constraints in migrations. You can add this manually in the database or use a package.
            // $table->check(""
            //     (nom_configuration = \'autre\' AND nom_configuration_autre IS NOT NULL AND nom_configuration_autre <> \'\')
            //     OR
            //     (nom_configuration <> \'autre\' AND nom_configuration_autre IS NULL)
            // ");
        });

        // Add FK from paniers.configuration_id once configurations exists.
        if (Schema::hasTable("paniers") && Schema::hasColumn("paniers", "configuration_id")) {
            Schema::table("paniers", function (Blueprint $table) {
                $table->foreign("configuration_id")
                    ->references("id")
                    ->on("configurations")
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable("paniers")) {
            Schema::table("paniers", function (Blueprint $table) {
                $table->dropForeign(["configuration_id"]);
            });
        }
        Schema::dropIfExists("configurations");
    }
};
