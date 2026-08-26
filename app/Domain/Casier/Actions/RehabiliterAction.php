<?php

namespace App\Domain\Casier\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Casier\Models\Condamnation;
use App\Domain\Casier\Models\Rehabilitation;
use App\Domain\Contracts\Horodatable;
use App\Models\User;
use InvalidArgumentException;

/**
 * Réhabilitation judiciaire (§6.10) : une juridiction a statué, sur requête
 * de la personne condamnée — distincte de la réhabilitation de plein droit
 * (App\Domain\Casier\Actions\DetecterRehabilitationsDePleinDroitAction),
 * qui ne suppose aucune décision humaine.
 */
class RehabiliterAction
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly Horodatable $horodatage,
    ) {}

    public function executer(Condamnation $condamnation, User $acteur): Condamnation
    {
        if (! $condamnation->estActive()) {
            throw new InvalidArgumentException('Cette condamnation n\'est plus active (déjà réhabilitée ou amnistiée).');
        }

        Rehabilitation::query()->create([
            'condamnation_id' => $condamnation->id,
            'type' => 'judiciaire',
            'decidee_at' => $this->horodatage->maintenant(),
            'decidee_par' => $acteur->id,
        ]);

        $condamnation->update(['statut' => 'rehabilitee']);

        $this->audit->consigner('casier.rehabilitation', auditable: $condamnation, acteur: $acteur, payloadSupplementaire: [
            'type' => 'judiciaire',
        ]);

        return $condamnation->refresh();
    }
}
