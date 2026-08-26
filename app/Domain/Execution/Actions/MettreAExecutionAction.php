<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Audiencement\Models\Decision;
use App\Domain\Audit\AuditService;
use App\Domain\Casier\Actions\EnregistrerCondamnationCasierAction;
use App\Domain\Execution\Models\DossierExecution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Mise à exécution d'une condamnation définitive (§6.9) : un acte
 * administratif, jamais automatique (§3), qui n'intervient qu'une fois le
 * caractère définitif acquis (Decision::estDefinitive()) — jamais avant,
 * même si le délai de recours semble une formalité dans le cas d'espèce.
 * Une relaxe, un acquittement ou une dispense de peine ne donnent lieu à
 * aucune exécution.
 *
 * Déclenche dans la foulée l'inscription au casier judiciaire (§6.10,
 * EnregistrerCondamnationCasierAction) : la mise à exécution est le seul
 * point du socle où le caractère définitif d'une condamnation est
 * effectivement vérifié par un acte humain, donc le point naturel pour
 * alimenter le casier plutôt qu'une tâche planifiée séparée.
 */
class MettreAExecutionAction
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly EnregistrerCondamnationCasierAction $enregistrerAuCasier,
    ) {}

    public function executer(Decision $decision, User $acteur): DossierExecution
    {
        if ($decision->decision !== 'condamnation') {
            throw new InvalidArgumentException('Seule une condamnation peut être mise à exécution.');
        }

        if (! $decision->estDefinitive()) {
            throw new InvalidArgumentException('Cette décision n\'est pas encore définitive (§6.7).');
        }

        if ($decision->dossierExecution()->exists()) {
            throw new InvalidArgumentException('Cette décision est déjà mise à exécution.');
        }

        return DB::transaction(function () use ($decision, $acteur) {
            $dossier = DossierExecution::query()->create([
                'decision_id' => $decision->id,
                'personne_id' => $decision->personne_id,
                'statut' => 'en_cours',
                'mise_a_execution_at' => now(),
                'mise_a_execution_par' => $acteur->id,
            ]);

            $this->audit->consigner('execution.mise_a_execution', auditable: $dossier, acteur: $acteur, payloadSupplementaire: [
                'decision_id' => $decision->id,
            ]);

            $this->enregistrerAuCasier->executer($decision, $acteur);

            return $dossier;
        });
    }
}
