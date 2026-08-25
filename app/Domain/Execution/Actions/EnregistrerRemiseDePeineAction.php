<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Execution\Models\Ecrou;
use App\Models\User;
use InvalidArgumentException;

/**
 * Remise de peine ou grâce (§6.9) : chaque remise reste tracée
 * individuellement (append-only), et recalcule immédiatement la date de
 * fin prévue de l'écrou.
 */
class EnregistrerRemiseDePeineAction
{
    private const MOTIFS_VALIDES = ['grace', 'reduction_peine'];

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(Ecrou $ecrou, int $jours, string $motif, User $acteur): Ecrou
    {
        if (! $ecrou->estEnDetention()) {
            throw new InvalidArgumentException('Une remise de peine ne peut être enregistrée que sur un écrou en cours.');
        }

        if (! in_array($motif, self::MOTIFS_VALIDES, true)) {
            throw new InvalidArgumentException("Motif inconnu : {$motif}.");
        }

        $ecrou->remisesPeine()->create([
            'jours' => $jours,
            'motif' => $motif,
            'decide_par' => $acteur->id,
            'decide_at' => now(),
        ]);

        $ecrou->update(['date_fin_prevue' => $ecrou->date_fin_prevue->clone()->subDays($jours)]);

        $this->audit->consigner('execution.remise_peine', auditable: $ecrou, acteur: $acteur, payloadSupplementaire: [
            'jours' => $jours,
            'motif' => $motif,
        ]);

        return $ecrou->refresh();
    }
}
