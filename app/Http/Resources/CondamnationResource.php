<?php

namespace App\Http\Resources;

use App\Domain\Casier\Models\Condamnation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Condamnation
 */
class CondamnationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'personne_id' => $this->personne_id,
            'numero_affaire' => $this->numero_affaire,
            'juridiction_nom' => $this->juridiction_nom,
            'infraction_libelle' => $this->infraction_libelle,
            'categorie_infraction' => $this->categorie_infraction,
            'peine_principale' => $this->peine_principale,
            'sursis' => $this->sursis,
            'condamnee_at' => $this->condamnee_at->toIso8601String(),
            'statut' => $this->statut,
            'inscrite_at' => $this->inscrite_at->toIso8601String(),
            'rehabilitation' => RehabilitationResource::make($this->whenLoaded('rehabilitation')),
            'amnistie' => AmnistieResource::make($this->whenLoaded('amnistie')),
        ];
    }
}
