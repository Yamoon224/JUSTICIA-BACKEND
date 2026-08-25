<?php

namespace App\Domain\GardeAVue\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\GardeAVue\Models\MesureGardeAVue;
use App\Models\User;
use InvalidArgumentException;

/**
 * Prolongation d'une garde à vue (§6.1), autorisée par le parquet. Le
 * contrôle qu'un magistrat autorise effectivement la prolongation (plutôt
 * qu'un tiers quelconque) revient à l'appelant (policy / permission
 * `parquet.gerer` sur $autorisePar) — cette action se concentre sur
 * l'enregistrement et le recalcul de l'échéance.
 */
class ProlongerGardeAVueAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(MesureGardeAVue $mesure, int $heures, User $autorisePar, User $agent): MesureGardeAVue
    {
        if (! $mesure->estEnCours()) {
            throw new InvalidArgumentException('Une garde à vue déjà clôturée ne peut pas être prolongée.');
        }

        $mesure->update([
            'duree_heures' => $mesure->duree_heures + $heures,
            'fin_prevue_at' => $mesure->fin_prevue_at->clone()->addHours($heures),
            'prolongation_heures' => $heures,
            'prolongation_autorisee_par' => $autorisePar->id,
            'prolongation_at' => now(),
            'statut' => 'prolongee',
        ]);

        $this->audit->consigner('gav.prolongation', auditable: $mesure, acteur: $agent, payloadSupplementaire: [
            'heures' => $heures,
            'autorisee_par' => $autorisePar->id,
        ]);

        return $mesure->refresh();
    }
}
