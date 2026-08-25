<?php

namespace App\Http\Resources;

use App\Domain\Parquet\Models\Requisition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Requisition
 */
class RequisitionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'contenu' => $this->contenu,
            'magistrat_id' => $this->magistrat_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
