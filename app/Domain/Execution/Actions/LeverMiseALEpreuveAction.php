<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Execution\Models\MiseALEpreuve;
use App\Models\User;
use InvalidArgumentException;

class LeverMiseALEpreuveAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(MiseALEpreuve $mise, User $acteur): MiseALEpreuve
    {
        if ($mise->statut === 'terminee') {
            throw new InvalidArgumentException('Cette mise à l\'épreuve est déjà terminée.');
        }

        $mise->update(['statut' => 'terminee']);

        $this->audit->consigner('execution.mise_a_l_epreuve_levee', auditable: $mise->dossierExecution, acteur: $acteur, payloadSupplementaire: [
            'mise_a_l_epreuve_id' => $mise->id,
        ]);

        return $mise->refresh();
    }
}
