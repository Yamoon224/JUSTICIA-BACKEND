<?php

namespace App\Http\Resources;

use App\Domain\Affaires\Models\Affaire;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Affaire
 */
class AffaireResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_affaire' => $this->numero_affaire,
            'statut' => $this->statut,
            'description' => $this->description,
            'date_ouverture' => $this->date_ouverture?->toDateString(),
            'ressort_id' => $this->ressort_id,
            'unite_id' => $this->unite_id,
            'infractions' => $this->whenLoaded('infractions', fn () => $this->infractions->map(fn ($infraction) => [
                'id' => $infraction->id,
                'code' => $infraction->code,
                'libelle' => $infraction->libelle,
                'categorie' => $infraction->categorie,
            ])),
            // §6.2 : une personne garde une ligne par changement de statut sur
            // l'affaire (l'historique n'est jamais écrasé — voir
            // RattacherPersonneAAffaireAction), mais l'API n'expose ici que
            // le statut courant de chacune : sans ce filtre, une personne
            // ayant changé de statut (ex. prévenu → relaxé) apparaîtrait en
            // double dans toute liste qui consomme ce champ.
            'personnes' => $this->whenLoaded('personnes', fn () => $this->personnes
                ->sortByDesc(fn ($personne) => $personne->pivot->id)
                ->unique('id')
                ->values()
                ->map(fn ($personne) => [
                    'id' => $personne->id,
                    'identifiant_unique' => $personne->identifiant_unique,
                    'nom_affichage' => $personne->nomAffichage(),
                    'statut' => $personne->pivot->statut,
                ])),
            'proces_verbaux' => $this->whenLoaded('procesVerbaux', fn () => ProcesVerbalResource::collection($this->procesVerbaux)),
            'scelles' => $this->whenLoaded('scelles', fn () => ScelleResource::collection($this->scelles)),
        ];
    }
}
