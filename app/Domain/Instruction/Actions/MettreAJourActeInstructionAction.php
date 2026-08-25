<?php

namespace App\Domain\Instruction\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Instruction\Models\ActeInstruction;
use App\Models\User;
use InvalidArgumentException;

/**
 * Suivi d'un acte d'instruction déjà enregistré (§6.6) : retour de
 * commission rogatoire reçu, rapport d'expertise déposé, acte réalisé.
 */
class MettreAJourActeInstructionAction
{
    private const STATUTS_VALIDES = ['realise', 'retour_recu', 'rapport_depose'];

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(ActeInstruction $acte, string $statut, User $acteur): ActeInstruction
    {
        if (! in_array($statut, self::STATUTS_VALIDES, true)) {
            throw new InvalidArgumentException("Statut inconnu : {$statut}.");
        }

        $acte->update(['statut' => $statut, 'date_realisation' => now()]);

        $this->audit->consigner('instruction.acte_mis_a_jour', auditable: $acte->dossierInstruction, acteur: $acteur, payloadSupplementaire: [
            'acte_id' => $acte->id,
            'statut' => $statut,
        ]);

        return $acte;
    }
}
