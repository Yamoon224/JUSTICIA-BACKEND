<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Execution\Models\Ecrou;
use App\Models\User;
use InvalidArgumentException;

/**
 * Levée d'écrou (§6.9) : « aucune détention au-delà du titre sans
 * signalement immédiat » — la levée reste ici une décision explicite d'un
 * agent, jamais déclenchée automatiquement à l'échéance (voir
 * DetecterEcheancesLiberationAction pour l'alerte correspondante).
 */
class LibererAction
{
    private const MOTIFS_VALIDES = ['terme', 'amenagement', 'grace'];

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(Ecrou $ecrou, string $motif, User $acteur): Ecrou
    {
        if (! $ecrou->estEnDetention()) {
            throw new InvalidArgumentException('Cet écrou est déjà levé.');
        }

        if (! in_array($motif, self::MOTIFS_VALIDES, true)) {
            throw new InvalidArgumentException("Motif inconnu : {$motif}.");
        }

        $ecrou->update([
            'statut' => 'libere',
            'date_liberation' => now(),
            'motif_liberation' => $motif,
        ]);

        $ecrou->dossierExecution->update(['statut' => 'terminee']);

        $this->audit->consigner('execution.levee_ecrou', auditable: $ecrou, acteur: $acteur, payloadSupplementaire: [
            'motif' => $motif,
        ]);

        return $ecrou->refresh();
    }
}
