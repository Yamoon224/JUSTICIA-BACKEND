<?php

namespace App\Domain\Audiencement\Actions;

use App\Domain\Audiencement\Models\Recours;
use App\Domain\Audit\AuditService;
use App\Models\User;
use InvalidArgumentException;

/**
 * Intégration de la décision rendue en appel/cassation (§6.8) :
 * confirmation, infirmation, cassation avec renvoi.
 *
 * Se limite ici à consigner l'issue du recours de façon opposable — la
 * répercussion effective sur le statut de la personne (§6.2) en cas
 * d'infirmation exige qu'une nouvelle décision soit rendue par la
 * juridiction saisie, ce qui n'est pas automatisable : elle passe par
 * EnregistrerDecisionAction comme n'importe quelle décision de jugement.
 * La cascade vers l'exécution et le casier (§14) suivra l'implémentation
 * de ces modules.
 */
class IntegrerDecisionRecoursAction
{
    private const ISSUES_VALIDES = ['confirmation', 'infirmation', 'cassation_avec_renvoi'];

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(Recours $recours, string $issue, User $acteur): Recours
    {
        if (! $recours->recevable) {
            throw new InvalidArgumentException('Un recours irrecevable ne peut pas recevoir de décision.');
        }

        if ($recours->estResolu()) {
            throw new InvalidArgumentException('Ce recours a déjà reçu une décision.');
        }

        if (! in_array($issue, self::ISSUES_VALIDES, true)) {
            throw new InvalidArgumentException("Issue inconnue : {$issue}.");
        }

        $recours->update([
            'decision_recours' => $issue,
            'decision_recours_at' => now(),
        ]);

        $this->audit->consigner('audiencement.recours_decision', auditable: $recours, acteur: $acteur, payloadSupplementaire: [
            'issue' => $issue,
        ]);

        return $recours->refresh();
    }
}
