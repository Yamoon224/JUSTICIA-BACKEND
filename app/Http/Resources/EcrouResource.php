<?php

namespace App\Http\Resources;

use App\Domain\Execution\Models\Ecrou;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ecrou
 */
class EcrouResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_ecrou' => $this->numero_ecrou,
            'personne_id' => $this->personne_id,
            'etablissement_id' => $this->etablissement_id,
            'date_ecrou' => $this->date_ecrou->toIso8601String(),
            'duree_jours' => $this->duree_jours,
            'detention_provisoire_imputee_jours' => $this->detention_provisoire_imputee_jours,
            'date_fin_prevue' => $this->date_fin_prevue->toIso8601String(),
            'statut' => $this->statut,
            'date_liberation' => $this->date_liberation?->toIso8601String(),
            'motif_liberation' => $this->motif_liberation,
            'echeance_depassee' => $this->echeanceDepassee(),
            'remises_peine' => RemisePeineResource::collection($this->whenLoaded('remisesPeine')),
            'amenagements' => AmenagementPeineResource::collection($this->whenLoaded('amenagements')),
            'transferts' => TransfertEcrouResource::collection($this->whenLoaded('transferts')),
        ];
    }
}
