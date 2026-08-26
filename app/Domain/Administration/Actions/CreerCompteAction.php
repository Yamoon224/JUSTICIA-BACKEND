<?php

namespace App\Domain\Administration\Actions;

use App\Domain\Audit\AuditService;
use App\Models\User;

/**
 * Création d'un compte agent (§6.13). Le compte est créé inactif : la
 * création seule ne suffit jamais à ouvrir l'accès — il faut la validation
 * d'un second administrateur, distinct du créateur (ValiderCompteAction,
 * §6.13 : « création ... de comptes à double validation »).
 */
class CreerCompteAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array{matricule: string, nom: string, prenom: string, email: ?string, password: string, service_id: ?int, ressort_id: ?int}  $donnees
     */
    public function executer(array $donnees, User $createur): User
    {
        $agent = User::query()->create([
            ...$donnees,
            'actif' => false,
            'cree_par' => $createur->id,
        ]);

        $this->audit->consigner('administration.compte_cree', auditable: $agent, acteur: $createur);

        return $agent;
    }
}
