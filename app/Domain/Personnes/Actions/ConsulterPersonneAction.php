<?php

namespace App\Domain\Personnes\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Personnes\Models\Personne;
use App\Models\User;

/**
 * Consultation d'une fiche personne (§6.2) : « toute consultation est
 * journalisée avec motif ». Le motif est donc exigé ici, pas laissé
 * optionnel comme pour un simple log applicatif.
 */
class ConsulterPersonneAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(Personne $personne, User $agent, string $motif): Personne
    {
        $this->audit->consigner('personnes.consultation', auditable: $personne, acteur: $agent, motif: $motif);

        return $personne->load(['piecesIdentite', 'representantLegal', 'documents']);
    }
}
