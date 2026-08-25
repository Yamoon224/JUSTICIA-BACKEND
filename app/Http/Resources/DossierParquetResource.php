<?php

namespace App\Http\Resources;

use App\Domain\Parquet\Models\DossierParquet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DossierParquet
 */
class DossierParquetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'affaire' => AffaireResource::make($this->whenLoaded('affaire')),
            'magistrat_id' => $this->magistrat_id,
            'recu_at' => $this->recu_at?->toIso8601String(),
            'affecte_at' => $this->affecte_at?->toIso8601String(),
            'orientation' => $this->orientation,
            'motif_classement_id' => $this->motif_classement_id,
            'oriente_at' => $this->oriente_at?->toIso8601String(),
            'oriente_par' => $this->oriente_par,
            'requisitions' => RequisitionResource::collection($this->whenLoaded('requisitions')),
        ];
    }
}
