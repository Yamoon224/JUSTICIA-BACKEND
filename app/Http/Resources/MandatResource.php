<?php

namespace App\Http\Resources;

use App\Domain\Instruction\Models\Mandat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Mandat
 */
class MandatResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'personne_id' => $this->personne_id,
            'type' => $this->type,
            'emis_at' => $this->emis_at->toIso8601String(),
            'diffuse_at' => $this->diffuse_at?->toIso8601String(),
            'execute_at' => $this->execute_at?->toIso8601String(),
        ];
    }
}
