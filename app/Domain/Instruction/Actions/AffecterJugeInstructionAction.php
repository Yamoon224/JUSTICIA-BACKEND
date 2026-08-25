<?php

namespace App\Domain\Instruction\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Instruction\Models\DossierInstruction;
use App\Models\User;
use InvalidArgumentException;

/**
 * Affectation d'un dossier d'information à un juge d'instruction (§6.6).
 */
class AffecterJugeInstructionAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(DossierInstruction $dossier, User $juge, User $acteur): DossierInstruction
    {
        if (! $juge->hasRole('juge_instruction') || $juge->ressort_id !== $dossier->affaire->ressort_id) {
            throw new InvalidArgumentException('Le juge doit être un juge d\'instruction du ressort de l\'affaire.');
        }

        $dossier->update(['juge_instruction_id' => $juge->id]);

        $this->audit->consigner('instruction.affectation', auditable: $dossier, acteur: $acteur, payloadSupplementaire: [
            'juge_instruction_id' => $juge->id,
        ]);

        return $dossier;
    }
}
