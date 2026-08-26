<?php

namespace App\Domain\Administration\Actions;

use App\Domain\Audit\AuditService;
use App\Models\User;
use InvalidArgumentException;

/**
 * Seconde validation, obligatoirement par un administrateur distinct du
 * créateur (§6.13) : sans quoi la « double validation » ne serait qu'une
 * formalité qu'un seul agent pourrait s'auto-accorder.
 */
class ValiderCompteAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(User $agent, User $validateur): User
    {
        if ($agent->estValide()) {
            throw new InvalidArgumentException('Ce compte a déjà été validé.');
        }

        if ($agent->cree_par !== null && $agent->cree_par === $validateur->id) {
            throw new InvalidArgumentException(
                'Le créateur du compte ne peut pas être également son validateur (double validation, §6.13).'
            );
        }

        $agent->update([
            'actif' => true,
            'valide_par' => $validateur->id,
            'valide_at' => now(),
        ]);

        $this->audit->consigner('administration.compte_valide', auditable: $agent, acteur: $validateur);

        return $agent->refresh();
    }
}
