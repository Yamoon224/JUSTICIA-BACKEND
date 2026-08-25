<?php

namespace App\Domain\Instruction\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Instruction\Models\MesureSurete;
use App\Models\User;
use InvalidArgumentException;

/**
 * Renouvellement d'une détention provisoire (§6.6) : décision toujours
 * humaine (§3) — une échéance dépassée sans décision est signalée en
 * priorité absolue (voir DetecterEcheancesDetentionAction), jamais
 * prolongée automatiquement.
 */
class RenouvelerDetentionProvisoireAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(MesureSurete $mesure, int $jours, User $juge): MesureSurete
    {
        if (! $mesure->estEnCours() || $mesure->type !== 'detention_provisoire') {
            throw new InvalidArgumentException('Seule une détention provisoire en cours peut être renouvelée.');
        }

        $mesure->update([
            'duree_jours' => $mesure->duree_jours + $jours,
            'fin_prevue_at' => $mesure->fin_prevue_at->clone()->addDays($jours),
            'renouvele_le' => now(),
        ]);

        $this->audit->consigner('instruction.detention_renouvelee', auditable: $mesure, acteur: $juge, payloadSupplementaire: [
            'jours' => $jours,
        ]);

        return $mesure->refresh();
    }
}
