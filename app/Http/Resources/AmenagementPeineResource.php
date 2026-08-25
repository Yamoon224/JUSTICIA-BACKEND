<?php

namespace App\Http\Resources;

use App\Domain\Execution\Models\AmenagementPeine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AmenagementPeine
 */
class AmenagementPeineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'decide_at' => $this->decide_at->toIso8601String(),
        ];
    }
}
