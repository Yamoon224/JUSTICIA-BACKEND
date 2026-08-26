<?php

namespace App\Domain\Casier\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Casier\Models\Amnistie;
use App\Domain\Casier\Models\Condamnation;
use App\Domain\Contracts\Horodatable;
use App\Models\User;
use InvalidArgumentException;

/**
 * Amnistie d'une condamnation (§6.10) : efface la mention de tous les
 * bulletins, y compris le B1 (contrairement à une réhabilitation) — décision
 * légale ou réglementaire explicite, jamais automatique ; le texte de
 * référence est obligatoire pour rester traçable et opposable.
 */
class AmnistierAction
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly Horodatable $horodatage,
    ) {}

    public function executer(Condamnation $condamnation, string $texteReference, User $acteur): Condamnation
    {
        if (! $condamnation->estActive()) {
            throw new InvalidArgumentException('Cette condamnation n\'est plus active (déjà réhabilitée ou amnistiée).');
        }

        Amnistie::query()->create([
            'condamnation_id' => $condamnation->id,
            'texte_reference' => $texteReference,
            'decidee_at' => $this->horodatage->maintenant(),
            'decidee_par' => $acteur->id,
        ]);

        $condamnation->update(['statut' => 'amnistiee']);

        $this->audit->consigner('casier.amnistie', auditable: $condamnation, acteur: $acteur, payloadSupplementaire: [
            'texte_reference' => $texteReference,
        ]);

        return $condamnation->refresh();
    }
}
