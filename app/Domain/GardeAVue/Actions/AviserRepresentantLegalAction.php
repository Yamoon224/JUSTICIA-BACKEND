<?php

namespace App\Domain\GardeAVue\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\GardeAVue\Models\MesureGardeAVue;
use App\Models\User;
use InvalidArgumentException;

/**
 * Régime spécifique mineurs (§6.1) : avis obligatoire au représentant
 * légal, tracé séparément des notifications de droits génériques.
 */
class AviserRepresentantLegalAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(MesureGardeAVue $mesure, User $agent): MesureGardeAVue
    {
        if (! $mesure->mineur) {
            throw new InvalidArgumentException("L'avis au représentant légal ne s'applique qu'aux mineurs.");
        }

        $mesure->update(['avis_representant_legal_at' => now()]);

        $this->audit->consigner('gav.avis_representant_legal', auditable: $mesure, acteur: $agent);

        return $mesure->refresh();
    }
}
