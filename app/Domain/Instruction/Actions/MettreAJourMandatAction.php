<?php

namespace App\Domain\Instruction\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Instruction\Models\Mandat;
use App\Models\User;
use InvalidArgumentException;

/**
 * Suivi d'un mandat déjà émis (§6.6) : diffusion puis exécution tracées
 * indépendamment.
 */
class MettreAJourMandatAction
{
    private const ETAPES_VALIDES = ['diffuse', 'execute'];

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(Mandat $mandat, string $etape, User $acteur): Mandat
    {
        if (! in_array($etape, self::ETAPES_VALIDES, true)) {
            throw new InvalidArgumentException("Étape inconnue : {$etape}.");
        }

        $colonne = $etape === 'diffuse' ? 'diffuse_at' : 'execute_at';
        $mandat->update([$colonne => now()]);

        $this->audit->consigner('instruction.mandat_'.$etape, auditable: $mandat->dossierInstruction, acteur: $acteur, payloadSupplementaire: [
            'mandat_id' => $mandat->id,
        ]);

        return $mandat;
    }
}
