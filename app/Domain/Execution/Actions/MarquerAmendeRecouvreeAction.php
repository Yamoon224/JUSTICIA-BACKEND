<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Execution\Models\Amende;
use App\Models\User;
use InvalidArgumentException;

class MarquerAmendeRecouvreeAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(Amende $amende, User $acteur): Amende
    {
        if ($amende->statut === 'recouvree') {
            throw new InvalidArgumentException('Cette amende est déjà marquée recouvrée.');
        }

        $amende->update(['statut' => 'recouvree']);

        $this->audit->consigner('execution.amende_recouvree', auditable: $amende->dossierExecution, acteur: $acteur, payloadSupplementaire: [
            'amende_id' => $amende->id,
        ]);

        return $amende->refresh();
    }
}
