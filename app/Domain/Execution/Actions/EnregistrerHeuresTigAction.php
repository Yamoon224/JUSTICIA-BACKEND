<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Execution\Models\TravailInteretGeneral;
use App\Models\User;
use InvalidArgumentException;

class EnregistrerHeuresTigAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(TravailInteretGeneral $tig, int $heures, User $acteur): TravailInteretGeneral
    {
        if ($tig->estTermine()) {
            throw new InvalidArgumentException('Ce TIG est déjà terminé.');
        }

        $totalEffectuees = min($tig->heures_requises, $tig->heures_effectuees + $heures);

        $tig->update([
            'heures_effectuees' => $totalEffectuees,
            'statut' => $totalEffectuees >= $tig->heures_requises ? 'terminee' : 'en_cours',
        ]);

        $this->audit->consigner('execution.tig_heures', auditable: $tig->dossierExecution, acteur: $acteur, payloadSupplementaire: [
            'tig_id' => $tig->id,
            'heures_ajoutees' => $heures,
            'heures_effectuees' => $totalEffectuees,
        ]);

        return $tig->refresh();
    }
}
