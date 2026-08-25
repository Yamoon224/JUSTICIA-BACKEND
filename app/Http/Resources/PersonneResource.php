<?php

namespace App\Http\Resources;

use App\Domain\Personnes\Models\Personne;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Personne
 */
class PersonneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'identifiant_unique' => $this->identifiant_unique,
            'type' => $this->type,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'nom_affichage' => $this->nomAffichage(),
            'alias' => $this->alias,
            'date_naissance' => $this->date_naissance?->toDateString(),
            'lieu_naissance' => $this->lieu_naissance,
            'sexe' => $this->sexe,
            'raison_sociale' => $this->raison_sociale,
            'adresse' => $this->adresse,
            'pieces_identite' => $this->whenLoaded('piecesIdentite', fn () => $this->piecesIdentite->map(fn ($piece) => [
                'type' => $piece->type,
                'numero' => $piece->numero,
            ])),
        ];
    }
}
