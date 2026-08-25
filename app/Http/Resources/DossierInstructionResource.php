<?php

namespace App\Http\Resources;

use App\Domain\Instruction\Models\DossierInstruction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DossierInstruction
 */
class DossierInstructionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'affaire' => AffaireResource::make($this->whenLoaded('affaire')),
            'juge_instruction_id' => $this->juge_instruction_id,
            'ouvert_at' => $this->ouvert_at->toIso8601String(),
            'statut' => $this->statut,
            'ordonnance' => $this->ordonnance,
            'ordonnance_at' => $this->ordonnance_at?->toIso8601String(),
            'ordonnance_par' => $this->ordonnance_par,
            'delai_recours_expire_at' => $this->delai_recours_expire_at?->toIso8601String(),
            'actes' => ActeInstructionResource::collection($this->whenLoaded('actes')),
            'mandats' => MandatResource::collection($this->whenLoaded('mandats')),
            'mesures_surete' => MesureSureteResource::collection($this->whenLoaded('mesuresSurete')),
        ];
    }
}
