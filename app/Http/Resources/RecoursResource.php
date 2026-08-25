<?php

namespace App\Http\Resources;

use App\Domain\Audiencement\Models\Recours;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Recours
 */
class RecoursResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'decision_id' => $this->decision_id,
            'type' => $this->type,
            'formee_par_personne_id' => $this->formee_par_personne_id,
            'formee_at' => $this->formee_at->toIso8601String(),
            'recevable' => $this->recevable,
            'effet_suspensif' => $this->effet_suspensif,
            'decision_recours' => $this->decision_recours,
            'decision_recours_at' => $this->decision_recours_at?->toIso8601String(),
        ];
    }
}
