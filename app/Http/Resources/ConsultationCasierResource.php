<?php

namespace App\Http\Resources;

use App\Domain\Casier\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Consultation
 */
class ConsultationCasierResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type_bulletin' => $this->type_bulletin,
            'motif' => $this->motif,
            'consultee_at' => $this->consultee_at->toIso8601String(),
            'consultee_par' => $this->whenLoaded('consultePar', fn () => [
                'id' => $this->consultePar->id,
                'nom_complet' => "{$this->consultePar->prenom} {$this->consultePar->nom}",
            ]),
        ];
    }
}
