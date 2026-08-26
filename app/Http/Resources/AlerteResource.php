<?php

namespace App\Http\Resources;

use App\Domain\Alertes\Models\Alerte;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Alerte
 */
class AlerteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'niveau' => $this->niveau,
            'message' => $this->message,
            'lue' => $this->estLue(),
            'lue_at' => $this->lue_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
