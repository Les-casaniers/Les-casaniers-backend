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


            // Type de configuration
            $table->enum("nom_configuration", [
                "cpu","carte_mere","gpu","ram","ssd","hdd","stockage",
                "alimentation","boitier","refroidissement","ventilateur",
                "ecran","clavier","souris","os","reseau","autre"
            ]);

            
            $table->string("type", 190)->nullable();
            $table->text("detail")->nullable();

            $table->string("capacite", 190)->nullable();

            // Prix total
            $table->decimal("prix_total", 12, 2)->nullable();

            // Dates custom
            $table->timestamp("date_creation")->useCurrent();
            $table->timestamp("date_modification")
                ->useCurrent()
                ->useCurrentOnUpdate();

            // Index
            $table->index("produit_id");

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
