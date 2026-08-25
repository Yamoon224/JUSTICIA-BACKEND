<?php

namespace App\Http\Resources;

use App\Domain\Execution\Models\RemisePeine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RemisePeine
 */
class RemisePeineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jours' => $this->jours,
            'motif' => $this->motif,
            'decide_at' => $this->decide_at->toIso8601String(),
        ];
    }
}
