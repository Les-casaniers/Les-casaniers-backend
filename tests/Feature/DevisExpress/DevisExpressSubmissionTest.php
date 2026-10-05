<?php

namespace Tests\Feature\DevisExpress;

use App\Models\Admin;
use App\Models\DevisExpress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevisExpressSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_submit_devis_express_and_admin_can_read_it(): void
    {
        $admin = Admin::create([
            'prenom' => 'Admin',
            'nom' => 'Test',
            'email' => 'admin-devis@example.com',
            'telephone' => '0300000000',
            'mot_de_passe' => 'password',
            'poste' => 'admin',
            'statut' => 'actif',
        ]);

        $payload = [
            'nom' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'telephone' => '0341234567',
            'entreprise' => 'Casanier',
            'besoin' => 'Une machine pour le bureau',
            'budget' => '500000',
            'date_souhaitee' => '2026-11-15',
            'message' => 'Besoin d’un ordinateur performant pour la comptabilité.',
        ];

        $response = $this->postJson('/api/devis-express', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('devis_express', [
            'email' => 'jean@example.com',
            'besoin' => 'Une machine pour le bureau',
        ]);

        $responseAdmin = $this->actingAs($admin, 'web')->getJson('/api/admin/devis-express');

        $responseAdmin->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
