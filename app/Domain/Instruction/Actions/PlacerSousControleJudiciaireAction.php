<?php

namespace App\Domain\Instruction\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Instruction\Models\DossierInstruction;
use App\Domain\Instruction\Models\MesureSurete;
use App\Domain\Personnes\Models\Personne;
use App\Models\User;

/**
 * Contrôle judiciaire (§6.6) : alternative à la détention, obligations
 * suivies jusqu'à mainlevée explicite (pas d'échéance calculée — voir
 * LeverMesureSureteAction).
 */
class PlacerSousControleJudiciaireAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(DossierInstruction $dossier, Personne $personne, string $obligations, User $juge): MesureSurete
    {
        $mesure = $dossier->mesuresSurete()->create([
            'personne_id' => $personne->id,
            'type' => 'controle_judiciaire',
            'debut_at' => now(),
            'obligations' => $obligations,
            'autorise_par' => $juge->id,
        ]);

        $this->audit->consigner('instruction.controle_judiciaire', auditable: $mesure, acteur: $juge, payloadSupplementaire: [
            'personne_id' => $personne->id,
        ]);

        return $mesure;
    }
}
