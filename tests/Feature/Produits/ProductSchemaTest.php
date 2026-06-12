<?php

namespace Tests\Feature\Produits;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductSchemaTest extends TestCase
{
    public function test_produits_table_includes_sous_categorie_column(): void
    {
        $this->assertTrue(Schema::hasColumn('produits', 'id_sous_categorie'));
    }
}
