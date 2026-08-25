<?php

namespace App\Domain\Instruction\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Instruction\Models\DossierInstruction;
use App\Domain\Instruction\Models\Mandat;
use App\Domain\Personnes\Models\Personne;
use App\Models\User;

/**
 * Émission d'un mandat (§6.6) : comparution, amener, dépôt, arrêt.
 * Diffusion et exécution sont tracées séparément (MettreAJourMandatAction).
 */
class EmettreMandatAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(DossierInstruction $dossier, Personne $personne, string $type, User $acteur): Mandat
    {
        $mandat = $dossier->mandats()->create([
            'personne_id' => $personne->id,
            'type' => $type,
            'emis_at' => now(),
            'emis_par' => $acteur->id,
        ]);

        $this->audit->consigner('instruction.mandat_emis', auditable: $dossier, acteur: $acteur, payloadSupplementaire: [
            'mandat_id' => $mandat->id,
            'type' => $type,
            'personne_id' => $personne->id,
        ]);

        return $mandat;
    }
}
