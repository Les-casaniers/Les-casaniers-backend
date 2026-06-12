<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Utilisateur;
use App\Mail\NewsletterHebdoMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendHebdoNewsletter extends Command
{
    protected $signature = 'newsletter:hebdo {--test=}';
    protected $description = 'Envoyer la newsletter hebdomadaire';

    public function handle()
    {
        // Mode test
        $testEmail = $this->option('test');
        
        if ($testEmail) {
            $this->info("🧪 Mode test - Envoi à: {$testEmail}");
            
            $testUser = Utilisateur::first(); // Récupère le premier utilisateur
            if ($testUser) {
                $testUser->email = $testEmail;
                $offres = $this->getOffresSemaine();
                Mail::to($testEmail)->send(new NewsletterHebdoMail($testUser, $offres));
                $this->info("✅ Email test envoyé!");
            } else {
                // Créer un utilisateur temporaire
                $tempUser = new Utilisateur();
                $tempUser->prenom = "Test";
                $tempUser->nom = "User";
                $tempUser->email = $testEmail;
                $offres = $this->getOffresSemaine();
                Mail::to($testEmail)->send(new NewsletterHebdoMail($tempUser, $offres));
                $this->info("✅ Email test envoyé!");
            }
            return;
        }

        // Envoi à tous les utilisateurs
        $this->info("📧 Début de l'envoi de la newsletter...");
        
        $users = Utilisateur::where('statut', 'actif')->get();
        $this->info("👥 " . $users->count() . " utilisateurs trouvés");

        $offres = $this->getOffresSemaine();
        $success = 0;
        $errors = 0;

        foreach ($users as $user) {
            try {
                Mail::to($user->email)->send(new NewsletterHebdoMail($user, $offres));
                $success++;
                $this->info("✓ Envoyé à: {$user->email}");
                usleep(100000);
            } catch (\Exception $e) {
                $errors++;
                Log::error("Erreur envoi à {$user->email}: " . $e->getMessage());
                $this->error("✗ Échec: {$user->email}");
            }
        }

        $this->newLine();
        $this->info("✅ Newsletter envoyée!");
        $this->info("📨 Succès: {$success} | ❌ Échecs: {$errors}");
    }

    private function getOffresSemaine()
    {
        return [
            [
                'titre' => '🎮 NOUVEAU : RTX 5060 disponible',
                'description' => 'Précommandez maintenant'
            ],
            [
                'titre' => '💻 Offre spéciale PC Gamer',
                'description' => '-15% sur Gaming Pro'
            ],
            [
                'titre' => '⚡ Promotion SSD Samsung',
                'description' => '-10% sur tous les SSD'
            ],
            [
                'titre' => '🔧 Tutoriel du mois',
                'description' => 'Nettoyer son PC sans risque'
            ]
        ];
    }
}