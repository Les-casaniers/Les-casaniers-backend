<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable("paniers")) {
            Schema::create("paniers", function (Blueprint $table) {
                $table->id();

                // Utilisateur (nullable = invité)
                $table->foreignId("utilisateur_id")
                    ->nullable()
                    ->constrained("utilisateurs")
                    ->nullOnDelete();

                // Statut du panier
                $table->enum("statut", ["actif", "commande", "abandonne"])
                    ->default("actif");
            });
        }

        Schema::table("paniers", function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn("paniers", "produit_id")) {
                $table->foreignId("produit_id")
                    ->nullable()
                    ->constrained("produits")
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn("paniers", "configuration_id")) {
                // configurations table is created in a later migration.
                // Keep column now, attach FK later.
                $table->unsignedBigInteger("configuration_id")->nullable();
            }
            if (!Schema::hasColumn("paniers", "titre")) {
                $table->string("titre");
            }
            if (!Schema::hasColumn("paniers", "prix_unitaire")) {
                $table->decimal("prix_unitaire", 12, 2)->nullable();
            }
            if (!Schema::hasColumn("paniers", "quantite")) {
                $table->unsignedInteger("quantite")->default(1);
            }
            if (!Schema::hasColumn("paniers", "date_creation")) {
                $table->timestamp("date_creation")->useCurrent();
            }
            if (!Schema::hasColumn("paniers", "date_modification")) {
                $table->timestamp("date_modification")->useCurrent()->useCurrentOnUpdate();
            }

            // Empêche doublons panier
            $table->unique([
                "utilisateur_id",
                "statut",
                "produit_id",
                "configuration_id"
            ], "uq_panier_item");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("paniers");
    }
};
