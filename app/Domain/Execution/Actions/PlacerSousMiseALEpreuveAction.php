<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Execution\Models\DossierExecution;
use App\Domain\Execution\Models\MiseALEpreuve;
use App\Models\User;
use InvalidArgumentException;

/**
 * Sursis avec mise à l'épreuve (§6.9) : obligations suivies.
 */
class PlacerSousMiseALEpreuveAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(DossierExecution $dossier, string $obligations, User $acteur): MiseALEpreuve
    {
        if ($dossier->miseALEpreuve()->exists()) {
            throw new InvalidArgumentException('Une mise à l\'épreuve existe déjà pour ce dossier.');
        }

        $mise = $dossier->miseALEpreuve()->create([
            'obligations' => $obligations,
            'statut' => 'en_cours',
        ]);

        $this->audit->consigner('execution.mise_a_l_epreuve', auditable: $dossier, acteur: $acteur, payloadSupplementaire: [
            'mise_a_l_epreuve_id' => $mise->id,
        ]);

        return $mise;
    }
}
