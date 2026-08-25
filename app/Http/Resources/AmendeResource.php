<?php

namespace App\Http\Resources;

use App\Domain\Execution\Models\Amende;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Amende
 */
class AmendeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'montant' => $this->montant,
            'statut' => $this->statut,
            'transmise_at' => $this->transmise_at->toIso8601String(),
        ];
    }
}
