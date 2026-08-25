<?php

namespace App\Http\Resources;

use App\Domain\Audiencement\Models\Decision;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Decision
 */
class DecisionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'personne_id' => $this->personne_id,
            'decision' => $this->decision,
            'peine_principale' => $this->peine_principale,
            'sursis' => $this->sursis,
            'interets_civils' => $this->interets_civils,
            'rendue_at' => $this->rendue_at->toIso8601String(),
            'delai_recours_expire_at' => $this->delai_recours_expire_at->toIso8601String(),
            'est_definitive' => $this->estDefinitive(),
            'recours' => RecoursResource::collection($this->whenLoaded('recours')),
        ];
    }
}
