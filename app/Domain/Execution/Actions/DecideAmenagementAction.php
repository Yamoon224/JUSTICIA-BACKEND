<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Execution\Models\AmenagementPeine;
use App\Domain\Execution\Models\Ecrou;
use App\Models\User;
use InvalidArgumentException;

/**
 * Aménagement de peine (§6.9) : libération conditionnelle, semi-liberté,
 * placement à l'extérieur — un régime modifié, pas une levée d'écrou (voir
 * LibererAction pour la sortie effective, motif `amenagement`).
 */
class DecideAmenagementAction
{
    private const TYPES_VALIDES = ['liberation_conditionnelle', 'semi_liberte', 'placement_exterieur'];

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(Ecrou $ecrou, string $type, User $acteur): AmenagementPeine
    {
        if (! $ecrou->estEnDetention()) {
            throw new InvalidArgumentException('Seul un écrou en cours peut faire l\'objet d\'un aménagement.');
        }

        if (! in_array($type, self::TYPES_VALIDES, true)) {
            throw new InvalidArgumentException("Type d'aménagement inconnu : {$type}.");
        }

        $amenagement = $ecrou->amenagements()->create([
            'type' => $type,
            'decide_at' => now(),
            'decide_par' => $acteur->id,
        ]);

        $this->audit->consigner('execution.amenagement', auditable: $ecrou, acteur: $acteur, payloadSupplementaire: [
            'type' => $type,
        ]);

        return $amenagement;
    }
}
