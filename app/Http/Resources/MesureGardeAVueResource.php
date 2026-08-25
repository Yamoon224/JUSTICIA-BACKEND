<?php

namespace App\Http\Resources;

use App\Domain\GardeAVue\Models\MesureGardeAVue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MesureGardeAVue
 */
class MesureGardeAVueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'affaire_id' => $this->affaire_id,
            'personne_id' => $this->personne_id,
            'unite_id' => $this->unite_id,
            'debut_at' => $this->debut_at->toIso8601String(),
            'duree_heures' => $this->duree_heures,
            'fin_prevue_at' => $this->fin_prevue_at->toIso8601String(),
            'statut' => $this->statut,
            'issue' => $this->issue,
            'fin_reelle_at' => $this->fin_reelle_at?->toIso8601String(),
            'mineur' => $this->mineur,
            'avis_representant_legal_at' => $this->avis_representant_legal_at?->toIso8601String(),
            'echeance_depassee' => $this->echeanceDepassee(),
            'notifications_droits' => $this->whenLoaded('notificationsDroits', fn () => $this->notificationsDroits->map(fn ($n) => [
                'droit' => $n->droit,
                'notifie_at' => $n->notifie_at?->toIso8601String(),
            ])),
            'actes' => $this->whenLoaded('actes', fn () => $this->actes->map(fn ($acte) => [
                'type' => $acte->type,
                'debut_at' => $acte->debut_at->toIso8601String(),
                'fin_at' => $acte->fin_at?->toIso8601String(),
            ])),
        ];
    }
}
