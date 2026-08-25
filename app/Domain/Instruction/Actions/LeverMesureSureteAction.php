<?php

namespace App\Domain\Instruction\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Instruction\Models\MesureSurete;
use App\Models\User;
use InvalidArgumentException;

/**
 * Mainlevée d'une mesure de sûreté (§6.6) : mise en liberté (contrôle
 * judiciaire ou détention provisoire) ou fin naturelle à échéance.
 */
class LeverMesureSureteAction
{
    private const MOTIFS_VALIDES = ['mise_en_liberte', 'echeance'];

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(MesureSurete $mesure, string $motif, User $juge): MesureSurete
    {
        if (! $mesure->estEnCours()) {
            throw new InvalidArgumentException('Cette mesure de sûreté est déjà levée.');
        }

        if (! in_array($motif, self::MOTIFS_VALIDES, true)) {
            throw new InvalidArgumentException("Motif inconnu : {$motif}.");
        }

        $mesure->update([
            'statut' => 'terminee',
            'fin_reelle_at' => now(),
            'motif_fin' => $motif,
        ]);

        $this->audit->consigner('instruction.mesure_surete_levee', auditable: $mesure, acteur: $juge, payloadSupplementaire: [
            'motif' => $motif,
        ]);

        return $mesure->refresh();
    }
}
