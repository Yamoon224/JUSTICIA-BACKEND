<?php

namespace App\Http\Resources;

use App\Domain\Casier\Models\Rehabilitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Rehabilitation
 */
class RehabilitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'decidee_at' => $this->decidee_at->toIso8601String(),
        ];
    }
}
