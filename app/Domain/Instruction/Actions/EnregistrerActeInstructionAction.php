<?php

namespace App\Domain\Instruction\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Instruction\Models\ActeInstruction;
use App\Domain\Instruction\Models\DossierInstruction;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Actes d'instruction (§6.6) : interrogatoires, confrontations,
 * transports, commissions rogatoires, expertises.
 */
class EnregistrerActeInstructionAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(DossierInstruction $dossier, string $type, ?string $description, ?Carbon $datePrevue, User $acteur): ActeInstruction
    {
        $acte = $dossier->actes()->create([
            'type' => $type,
            'description' => $description,
            'date_prevue' => $datePrevue,
            'created_by' => $acteur->id,
            // Explicite plutôt que de compter sur le défaut SQL : create()
            // ne relit pas la ligne insérée pour la réponse API immédiate.
            'statut' => 'en_attente',
        ]);

        $this->audit->consigner('instruction.acte', auditable: $dossier, acteur: $acteur, payloadSupplementaire: [
            'acte_id' => $acte->id,
            'type' => $type,
        ]);

        return $acte;
    }
}
