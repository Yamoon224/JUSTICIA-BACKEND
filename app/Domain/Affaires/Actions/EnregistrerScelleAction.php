<?php

namespace App\Domain\Affaires\Actions;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Affaires\Models\Scelle;
use App\Domain\Audit\AuditService;
use App\Models\User;

/**
 * Enregistre un scellé (§6.4) et ouvre sa chaîne de conservation par un
 * premier mouvement de dépôt — un scellé sans mouvement initial ne serait
 * pas conforme à l'exigence de traçabilité continue.
 */
class EnregistrerScelleAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function executer(Affaire $affaire, string $numeroScelle, string $description, ?string $lieuSaisie, User $agent): Scelle
    {
        $scelle = Scelle::query()->create([
            'affaire_id' => $affaire->id,
            'numero_scelle' => $numeroScelle,
            'description' => $description,
            'lieu_saisie' => $lieuSaisie,
            'statut' => 'en_depot',
            'created_by' => $agent->id,
        ]);

        $scelle->mouvements()->create([
            'type' => 'depot',
            'recepteur_id' => $agent->id,
            'horodatage' => now(),
        ]);

        $this->audit->consigner('affaires.scelle.enregistrement', auditable: $scelle, acteur: $agent);

        return $scelle;
    }
}
