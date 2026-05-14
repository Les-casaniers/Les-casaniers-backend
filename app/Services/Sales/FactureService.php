<?php

namespace App\Services\Sales;

use App\Enums\Sales\FactureStatut;
use App\Models\Facture;
use App\Repositories\Sales\CommandeRepositoryInterface;
use App\Repositories\Sales\FactureRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FactureService
{
    public function __construct(
        private readonly FactureRepositoryInterface $factureRepository,
        private readonly CommandeRepositoryInterface $commandeRepository
    ) {
    }

    // ──────────────────────────────────────────────
    //  Lecture
    // ──────────────────────────────────────────────

    public function index(int $userId): Collection
    {
        return $this->factureRepository->allByUser($userId);
    }

    public function show(int $userId, int $id): Facture
    {
        $facture = $this->factureRepository->findForUser($id, $userId);

        if (!$facture) {
            throw ValidationException::withMessages([
                'facture_id' => ['Facture introuvable.'],
            ]);
        }

        return $facture;
    }

    public function adminIndex(): Collection
    {
        return $this->factureRepository->all();
    }

    public function adminShow(int $id): Facture
    {
        return $this->factureRepository->find($id);
    }

    // ──────────────────────────────────────────────
    //  Création
    // ──────────────────────────────────────────────

    /**
     * Crée une facture à partir d'une commande (par UUID).
     * Vérifie qu'aucune facture n'existe déjà pour cette commande.
     */
    public function createFromCommande(string $commandeUuid): Facture
    {
        return DB::transaction(function () use ($commandeUuid) {
            $items = $this->commandeRepository->findByUuidWithLock($commandeUuid);

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'commande_uuid' => ['Commande introuvable.'],
                ]);
            }

            if ($this->factureRepository->findByCommandeUuid($commandeUuid)) {
                throw ValidationException::withMessages([
                    'commande_uuid' => ['Cette commande possède déjà une facture.'],
                ]);
            }

            $first = $items->first();

            return $this->factureRepository->create([
                'commande_id' => $first->id,
                'facture_ref' => $this->factureRepository->nextReference(),
                'statut' => FactureStatut::Brouillon->value,
                'montant_total' => $this->calculateTotal($items),
                'devise' => $first->devise ?? 'MGA',
            ]);
        });
    }

    /**
     * Crée une facture seulement si elle n'existe pas encore pour cette commande.
     */
    public function createFromCommandeIfMissing(string $commandeUuid): Facture
    {
        $existing = $this->factureRepository->findByCommandeUuid($commandeUuid);

        if ($existing) {
            return $existing;
        }

        return $this->createFromCommande($commandeUuid);
    }

    // ──────────────────────────────────────────────
    //  Transitions de statut
    // ──────────────────────────────────────────────

    /**
     * Émet une facture brouillon (brouillon → émise).
     */
    public function emit(int $id): Facture
    {
        return DB::transaction(function () use ($id) {
            $facture = $this->factureRepository->find($id);

            if (!$facture->statut->peutTransitionVers(FactureStatut::Emise)) {
                throw ValidationException::withMessages([
                    'statut' => ['Seule une facture brouillon peut être émise.'],
                ]);
            }

            $updated = $this->factureRepository->update($id, [
                'statut' => FactureStatut::Emise->value,
                'date_emission' => now(),
            ]);

            $this->generateDocument($updated->id);

            return $this->factureRepository->find($updated->id);
        });
    }

    /**
     * Marque une facture émise comme payée.
     */
    public function markPaid(int $id, ?string $method = null): Facture
    {
        return DB::transaction(function () use ($id, $method) {
            $facture = $this->factureRepository->find($id);

            if (!$facture->statut->peutTransitionVers(FactureStatut::Payee)) {
                throw ValidationException::withMessages([
                    'statut' => ['Seule une facture émise peut être marquée comme payée.'],
                ]);
            }

            return $this->factureRepository->update($id, [
                'statut' => FactureStatut::Payee->value,
                'methode_paiement' => $method,
                'date_paiement' => now(),
            ]);
        });
    }

    /**
     * Annule une facture (impossible si déjà payée).
     */
    public function cancel(int $id): Facture
    {
        return DB::transaction(function () use ($id) {
            $facture = $this->factureRepository->find($id);

            if (!$facture->statut->estAnnulable()) {
                throw ValidationException::withMessages([
                    'statut' => ['Cette facture ne peut pas être annulée.'],
                ]);
            }

            return $this->factureRepository->update($id, [
                'statut' => FactureStatut::Annulee->value,
            ]);
        });
    }

    // ──────────────────────────────────────────────
    //  Document PDF
    // ──────────────────────────────────────────────

    public function documentPathForAdmin(int $id): string
    {
        $facture = $this->factureRepository->find($id);

        return $this->ensureDocument($facture);
    }

    public function documentPathForUser(int $id, int $userId): string
    {
        $facture = $this->show($userId, $id);

        return $this->ensureDocument($facture);
    }

    // ──────────────────────────────────────────────
    //  Méthodes privées
    // ──────────────────────────────────────────────

    private function ensureDocument(Facture $facture): string
    {
        if (!$facture->statut->estTelechargeable()) {
            throw ValidationException::withMessages([
                'statut' => ['La facture doit être émise avant téléchargement.'],
            ]);
        }

        if ($facture->pdf_path && Storage::disk('local')->exists($facture->pdf_path)) {
            return $facture->pdf_path;
        }

        return $this->generateDocument($facture->id);
    }

    private function generateDocument(int $factureId): string
    {
        $facture = $this->factureRepository->find($factureId);
        $commande = $facture->commande;
        $items = $this->commandeRepository->findByUuid($commande->commande_uuid);

        $pdf = $this->renderPdf($facture, $items);
        $path = 'factures/' . $facture->facture_ref . '.pdf';

        Storage::disk('local')->put($path, $pdf);
        $this->factureRepository->update($facture->id, ['pdf_path' => $path]);

        return $path;
    }

    private function calculateTotal($items): float
    {
        $first = $items->first();

        if ($first && (float) $first->total > 0) {
            return (float) $first->total;
        }

        $sousTotal = (float) $items->sum(
            fn ($item) => ((float) $item->prix_unitaire) * ((int) $item->quantite)
        );

        return $sousTotal + (float) ($first->livraison ?? 0);
    }

    private function renderPdf(Facture $facture, $items): string
    {
        $commande = $facture->commande;
        $client = $commande?->utilisateur;

        $lines = [
            'Facture ' . $facture->facture_ref,
            'Statut: ' . ($facture->statut instanceof FactureStatut ? $facture->statut->label() : $facture->statut),
            'Date emission: ' . (optional($facture->date_emission)->format('d/m/Y H:i') ?? '-'),
            'Commande: ' . ($commande->commande_uuid ?? '-'),
            'Client: ' . trim(($client->prenom ?? '') . ' ' . ($client->nom ?? '')) . ' - ' . ($client->email ?? '-'),
            '',
            'Produits',
        ];

        foreach ($items as $item) {
            $lineTotal = ((float) $item->prix_unitaire) * ((int) $item->quantite);
            $lines[] = $item->titre . ' | Qt: ' . (int) $item->quantite . ' | PU: ' .
                number_format((float) $item->prix_unitaire, 2, '.', ' ') . ' | Total: ' .
                number_format($lineTotal, 2, '.', ' ') . ' ' . $facture->devise;
        }

        $lines[] = '';
        $lines[] = 'Total facture: ' . number_format((float) $facture->montant_total, 2, '.', ' ') . ' ' . $facture->devise;

        $content = '';
        $y = 800;
        foreach ($lines as $index => $line) {
            $size = $index === 0 ? 18 : 11;
            $content .= "BT /F1 {$size} Tf 40 {$y} Td (" . $this->escapePdfText($line) . ") Tj ET\n";
            $y -= $index === 0 ? 28 : 18;
        }

        return $this->buildPdf($content);
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function buildPdf(string $content): string
    {
        $objects = [
            '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj',
            '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj',
            '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj',
            '4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj',
            "5 0 obj << /Length " . strlen($content) . " >> stream\n" . $content . "endstream endobj",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object . "\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}
