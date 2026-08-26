<?php

namespace App\Domain\Administration\Actions;

use App\Domain\Audit\AuditService;
use App\Models\User;

/**
 * Affectation des rôles d'un agent (§6.13 : « profils types + affectations
 * par ressort/service »). Remplace intégralement l'ensemble des rôles —
 * reflète l'intention de l'administrateur telle qu'exprimée dans le
 * formulaire (une checklist, pas un ajout incrémental).
 */
class AssignerRolesAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  list<string>  $roles
     */
    public function executer(User $agent, array $roles, User $administrateur): User
    {
        $agent->syncRoles($roles);

        $this->audit->consigner('administration.roles_modifies', auditable: $agent, acteur: $administrateur, payloadSupplementaire: [
            'roles' => $roles,
        ]);

        return $agent->refresh();
    }
}
