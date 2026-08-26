<?php

namespace App\Domain\Administration\Actions;

use App\Domain\Audit\AuditService;
use App\Models\User;
use InvalidArgumentException;

/**
 * Réactivation d'un compte suspendu (§6.13) — jamais d'un compte qui n'a
 * encore reçu aucune validation : ce serait contourner la double validation
 * de la création en passant par la réactivation.
 */
class ReactiverCompteAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(User $agent, User $administrateur): User
    {
        if ($agent->suspendu_at === null) {
            throw new InvalidArgumentException('Ce compte n\'est pas suspendu.');
        }

        if (! $agent->estValide()) {
            throw new InvalidArgumentException('Ce compte n\'a jamais été validé : utilisez la validation initiale.');
        }

        $agent->update(['actif' => true, 'suspendu_at' => null]);

        $this->audit->consigner('administration.compte_reactive', auditable: $agent, acteur: $administrateur);

        return $agent->refresh();
    }
}
