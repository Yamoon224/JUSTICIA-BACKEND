<?php

namespace App\Domain\GardeAVue\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\GardeAVue\Models\MesureGardeAVue;
use App\Models\User;
use InvalidArgumentException;

/**
 * Clôture d'une mesure de garde à vue (§6.1). L'issue est obligatoire :
 * remise en liberté, convocation ultérieure, ou déferrement au parquet —
 * avec horodatage de fin. Un déferrement déclenchera la suite de la chaîne
 * (bureau des arrivées du parquet) à partir de la Phase 4.
 */
class CloturerGardeAVueAction
{
    private const ISSUES_VALIDES = ['liberation', 'convocation', 'deferement'];

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(MesureGardeAVue $mesure, string $issue, User $agent): MesureGardeAVue
    {
        if (! $mesure->estEnCours()) {
            throw new InvalidArgumentException('Cette mesure de garde à vue est déjà clôturée.');
        }

        if (! in_array($issue, self::ISSUES_VALIDES, true)) {
            throw new InvalidArgumentException("Issue de garde à vue invalide : {$issue}.");
        }

        $mesure->update([
            'statut' => 'terminee',
            'issue' => $issue,
            'fin_reelle_at' => now(),
        ]);

        $this->audit->consigner('gav.cloture', auditable: $mesure, acteur: $agent, payloadSupplementaire: [
            'issue' => $issue,
        ]);

        return $mesure->refresh();
    }
}
