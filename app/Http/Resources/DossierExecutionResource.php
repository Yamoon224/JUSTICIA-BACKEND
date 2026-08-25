<?php

namespace App\Http\Resources;

use App\Domain\Execution\Models\DossierExecution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DossierExecution
 */
class DossierExecutionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'decision_id' => $this->decision_id,
            'personne_id' => $this->personne_id,
            'affaire' => $this->when(
                $this->relationLoaded('decision') && $this->decision->relationLoaded('dossierAudiencement'),
                fn () => AffaireResource::make($this->decision->dossierAudiencement->affaire),
            ),
            'statut' => $this->statut,
            'mise_a_execution_at' => $this->mise_a_execution_at->toIso8601String(),
            'ecrou' => EcrouResource::make($this->whenLoaded('ecrou')),
            'amende' => AmendeResource::make($this->whenLoaded('amende')),
            'tig' => TravailInteretGeneralResource::make($this->whenLoaded('tig')),
            'mise_a_l_epreuve' => MiseALEpreuveResource::make($this->whenLoaded('miseALEpreuve')),
        ];
    }
}
