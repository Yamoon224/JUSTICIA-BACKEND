<?php

namespace App\Domain\Administration\Actions;

use App\Domain\Audit\AuditService;
use App\Models\User;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

/**
 * Affectation des rôles d'un agent (§6.13 : « profils types + affectations
 * par ressort/service »). Remplace intégralement l'ensemble des rôles —
 * reflète l'intention de l'administrateur telle qu'exprimée dans le
 * formulaire (une checklist, pas un ajout incrémental).
 *
 * CONSTAT DE SÉCURITÉ (revue du 2026-08-27) : cette Action accordait
 * n'importe quel rôle sans restriction. `habilitations.gerer` est pensée
 * pour être déléguée sans donner par ailleurs `administration.gerer` (cf.
 * HabilitationController) — mais rien n'empêchait un titulaire de la seule
 * `habilitations.gerer` de s'auto-accorder (ou d'accorder à un complice) le
 * rôle `administrateur`, qui porte les deux permissions : élévation de
 * privilèges immédiate. Corrigé en réservant l'octroi de tout rôle portant
 * une permission d'administration (`administration.gerer`,
 * `habilitations.gerer`) aux titulaires de `administration.gerer` — sans
 * empêcher par ailleurs un administrateur d'affecter les rôles métier
 * ordinaires (opj, greffier...) qu'il ne détient pas lui-même, ce qui reste
 * son rôle légitime (§6.13).
 */
class AssignerRolesAction
{
    private const PERMISSIONS_D_ADMINISTRATION = ['administration.gerer', 'habilitations.gerer'];

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  list<string>  $roles
     */
    public function executer(User $agent, array $roles, User $administrateur): User
    {
        $this->verifierQueLAdministrateurPeutAccorderCesRoles($roles, $administrateur);

        $agent->syncRoles($roles);

        $this->audit->consigner('administration.roles_modifies', auditable: $agent, acteur: $administrateur, payloadSupplementaire: [
            'roles' => $roles,
        ]);

        return $agent->refresh();
    }

    /**
     * @param  list<string>  $roles
     */
    private function verifierQueLAdministrateurPeutAccorderCesRoles(array $roles, User $administrateur): void
    {
        $permissionsAccordees = Role::query()
            ->whereIn('name', $roles)
            ->with('permissions')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('name'));

        $permissionsAdministrationAccordees = $permissionsAccordees->intersect(self::PERMISSIONS_D_ADMINISTRATION);

        if ($permissionsAdministrationAccordees->isNotEmpty() && ! $administrateur->can('administration.gerer')) {
            throw new InvalidArgumentException(
                'Seul un titulaire de administration.gerer peut accorder un rôle portant : '
                .$permissionsAdministrationAccordees->unique()->implode(', ')
            );
        }
    }
}
