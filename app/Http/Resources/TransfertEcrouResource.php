<?php

namespace App\Http\Resources;

use App\Domain\Execution\Models\TransfertEcrou;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TransfertEcrou
 */
class TransfertEcrouResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'etablissement_origine_id' => $this->etablissement_origine_id,
            'etablissement_destination_id' => $this->etablissement_destination_id,
            'motif' => $this->motif,
            'transfere_at' => $this->transfere_at->toIso8601String(),
        ];
    }
}
