<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Execution\Models\DossierExecution;
use App\Domain\Execution\Models\TravailInteretGeneral;
use App\Models\User;
use InvalidArgumentException;

/**
 * Travail d'intérêt général (§6.9) : affectation et suivi des heures.
 */
class AffecterTigAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(DossierExecution $dossier, int $heuresRequises, ?string $affecteA, User $acteur): TravailInteretGeneral
    {
        if ($dossier->tig()->exists()) {
            throw new InvalidArgumentException('Un TIG existe déjà pour ce dossier.');
        }

        $tig = $dossier->tig()->create([
            'heures_requises' => $heuresRequises,
            'affecte_a' => $affecteA,
            'statut' => 'en_cours',
        ]);

        $this->audit->consigner('execution.tig_affecte', auditable: $dossier, acteur: $acteur, payloadSupplementaire: [
            'tig_id' => $tig->id,
            'heures_requises' => $heuresRequises,
        ]);

        return $tig;
    }
}
