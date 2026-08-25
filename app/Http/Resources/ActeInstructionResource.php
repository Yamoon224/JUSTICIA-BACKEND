<?php

namespace App\Http\Resources;

use App\Domain\Instruction\Models\ActeInstruction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ActeInstruction
 */
class ActeInstructionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'description' => $this->description,
            'date_prevue' => $this->date_prevue?->toDateString(),
            'date_realisation' => $this->date_realisation?->toIso8601String(),
            'statut' => $this->statut,
        ];
    }
}
