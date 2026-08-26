<?php

namespace App\Domain\Administration\Actions;

use App\Domain\Audit\AuditService;
use App\Models\User;
use InvalidArgumentException;

/**
 * Suspension d'un compte (§6.13). Volontairement actionnable par un seul
 * administrateur, à la différence de la création : une désactivation
 * d'urgence (compte compromis, agent suspendu de ses fonctions) est un
 * contrôle de sécurité qui doit pouvoir être immédiat, pas freiné par une
 * seconde validation.
 */
class SuspendreCompteAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(User $agent, User $administrateur, ?string $motif): User
    {
        if ($agent->suspendu_at !== null) {
            throw new InvalidArgumentException('Ce compte est déjà suspendu.');
        }

        $agent->update(['actif' => false, 'suspendu_at' => now()]);

        $this->audit->consigner('administration.compte_suspendu', auditable: $agent, acteur: $administrateur, motif: $motif);

        return $agent->refresh();
    }
}
