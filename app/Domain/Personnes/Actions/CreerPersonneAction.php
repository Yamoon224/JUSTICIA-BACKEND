<?php

namespace App\Domain\Personnes\Actions;

use App\Domain\Audit\AuditService;
use App\Domain\Personnes\Models\Personne;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Enregistre une nouvelle personne mise en cause dans le fichier central
 * (§6.2). La détection de doublons n'est jamais automatique : elle est
 * proposée par RechercherPersonnesAction et validée par un OPJ via
 * FusionnerPersonnesAction.
 */
class CreerPersonneAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $attributs
     */
    public function executer(array $attributs, User $agent): Personne
    {
        $personne = Personne::query()->create([
            ...$attributs,
            'identifiant_unique' => (string) Str::uuid(),
            'created_by' => $agent->id,
        ]);

        $this->audit->consigner('personnes.creation', auditable: $personne, acteur: $agent);

        return $personne;
    }
}
