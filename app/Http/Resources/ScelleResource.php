<?php

namespace App\Http\Resources;

use App\Domain\Affaires\Models\Scelle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Scelle
 */
class ScelleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_scelle' => $this->numero_scelle,
            'description' => $this->description,
            'lieu_saisie' => $this->lieu_saisie,
            'statut' => $this->statut,
            'mouvements' => $this->whenLoaded('mouvements', fn () => $this->mouvements->map(fn ($mouvement) => [
                'type' => $mouvement->type,
                'motif' => $mouvement->motif,
                'horodatage' => $mouvement->horodatage->toIso8601String(),
            ])),
        ];
    }
}
