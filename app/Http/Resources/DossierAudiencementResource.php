<?php

namespace App\Http\Resources;

use App\Domain\Audiencement\Models\DossierAudiencement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DossierAudiencement
 */
class DossierAudiencementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'affaire' => AffaireResource::make($this->whenLoaded('affaire')),
            'juridiction_id' => $this->juridiction_id,
            'chambre' => $this->chambre,
            'date_audience' => $this->date_audience?->toIso8601String(),
            'president_id' => $this->president_id,
            'greffier_id' => $this->greffier_id,
            'statut' => $this->statut,
            'renvois' => RenvoiAudienceResource::collection($this->whenLoaded('renvois')),
            'decisions' => DecisionResource::collection($this->whenLoaded('decisions')),
        ];
    }
}
