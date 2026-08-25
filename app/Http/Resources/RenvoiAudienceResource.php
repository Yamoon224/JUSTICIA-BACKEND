<?php

namespace App\Http\Resources;

use App\Domain\Audiencement\Models\RenvoiAudience;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RenvoiAudience
 */
class RenvoiAudienceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ancienne_date_audience' => $this->ancienne_date_audience?->toIso8601String(),
            'nouvelle_date_audience' => $this->nouvelle_date_audience->toIso8601String(),
            'motif' => $this->motif,
        ];
    }
}
