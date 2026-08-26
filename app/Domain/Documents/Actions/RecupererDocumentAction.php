<?php

namespace App\Domain\Documents\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Contracts\StockageDocuments;
use App\Domain\Documents\Models\Document;
use App\Models\User;
use RuntimeException;

/**
 * Lecture d'une pièce versée (§6.2, §6.3, §6.4) : vérifie l'empreinte
 * d'intégrité avant de rendre le contenu déchiffré, et journalise la
 * consultation — même exigence que pour le casier (§6.10, §8) dès lors
 * qu'il peut s'agir d'une pièce à caractère personnel (photo, pièce
 * d'identité).
 */
class RecupererDocumentAction
{
    public function __construct(
        private readonly StockageDocuments $stockage,
        private readonly AuditService $audit,
    ) {}

    public function executer(Document $document, User $agent, ?string $motif): string
    {
        $contenu = $this->stockage->lire($document->chemin_stockage);

        if (! hash_equals($document->hash_integrite, hash('sha256', $contenu))) {
            throw new RuntimeException("Intégrité compromise pour le document #{$document->id}.");
        }

        $this->audit->consigner('documents.consultation', auditable: $document, acteur: $agent, motif: $motif);

        return $contenu;
    }
}
