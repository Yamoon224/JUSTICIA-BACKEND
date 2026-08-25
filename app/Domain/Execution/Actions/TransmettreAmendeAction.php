<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Execution\Models\Amende;
use App\Domain\Execution\Models\DossierExecution;
use App\Models\User;
use InvalidArgumentException;

/**
 * Recouvrement des amendes (§6.9) : état transmis au Trésor public.
 */
class TransmettreAmendeAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(DossierExecution $dossier, int $montant, User $acteur): Amende
    {
        if ($dossier->amende()->exists()) {
            throw new InvalidArgumentException('Une amende existe déjà pour ce dossier.');
        }

        $amende = $dossier->amende()->create([
            'montant' => $montant,
            'statut' => 'transmise_tresor',
            'transmise_at' => now(),
            'transmise_par' => $acteur->id,
        ]);

        $this->audit->consigner('execution.amende_transmise', auditable: $dossier, acteur: $acteur, payloadSupplementaire: [
            'amende_id' => $amende->id,
            'montant' => $montant,
        ]);

        return $amende;
    }
}
