<?php

namespace App\Http\Resources;

use App\Domain\Instruction\Models\MesureSurete;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MesureSurete
 */
class MesureSureteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'personne_id' => $this->personne_id,
            'type' => $this->type,
            'debut_at' => $this->debut_at->toIso8601String(),
            'duree_jours' => $this->duree_jours,
            'fin_prevue_at' => $this->fin_prevue_at?->toIso8601String(),
            'renouvele_le' => $this->renouvele_le?->toIso8601String(),
            'obligations' => $this->obligations,
            'statut' => $this->statut,
            'fin_reelle_at' => $this->fin_reelle_at?->toIso8601String(),
            'motif_fin' => $this->motif_fin,
            'echeance_depassee' => $this->echeanceDepassee(),
        ];
    }
}
