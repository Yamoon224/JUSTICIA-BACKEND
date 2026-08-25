<?php

namespace App\Http\Resources;

use App\Domain\Audiencement\Models\Decision;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vue « décisions en attente de mise à exécution » (§6.9), destinée au
 * service pénitentiaire — qui n'a pas d'accès direct au dossier
 * d'audiencement (App\Policies\DossierAudiencementPolicy exige
 * `audiencement.gerer`, pas `execution.gerer`). Volontairement plus légère
 * qu'un DecisionResource complet : juste de quoi identifier l'affaire et la
 * personne condamnée avant d'agir.
 *
 * @mixin Decision
 */
class DecisionAExecuterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'peine_principale' => $this->peine_principale,
            'rendue_at' => $this->rendue_at->toIso8601String(),
            'delai_recours_expire_at' => $this->delai_recours_expire_at->toIso8601String(),
            'affaire' => [
                'id' => $this->dossierAudiencement->affaire->id,
                'numero_affaire' => $this->dossierAudiencement->affaire->numero_affaire,
            ],
            'personne' => [
                'id' => $this->personne->id,
                'nom_affichage' => $this->personne->nomAffichage(),
            ],
        ];
    }
}
