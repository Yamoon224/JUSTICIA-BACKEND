<?php

namespace App\Domain\Affaires\Actions;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Audit\AuditService;
use App\Domain\Personnes\Models\Personne;
use App\Models\User;

/**
 * Rattache une personne à une affaire avec un statut explicite (§6.2) :
 * suspect, victime, témoin... Un changement de statut (ex. suspect →
 * mis en examen) crée une nouvelle ligne plutôt que d'écraser la
 * précédente, pour garder l'historique complet des statuts successifs.
 */
class RattacherPersonneAAffaireAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(Affaire $affaire, Personne $personne, string $statut, User $agent): void
    {
        $affaire->personnes()->attach($personne->id, [
            'statut' => $statut,
            'depuis' => now(),
        ]);

        $this->audit->consigner('affaires.rattachement_personne', auditable: $affaire, acteur: $agent, payloadSupplementaire: [
            'personne_id' => $personne->id,
            'statut' => $statut,
        ]);
    }
}
