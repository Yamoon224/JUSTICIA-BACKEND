<?php

namespace App\Http\Resources;

use App\Domain\Affaires\Models\ProcesVerbal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProcesVerbal
 */
class ProcesVerbalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cote' => $this->cote,
            'type' => $this->type,
            'contenu' => $this->contenu,
            'signe' => $this->estSigne(),
            'signe_at' => $this->signe_at?->toIso8601String(),
            'rectifie_par_pv_id' => $this->rectifie_par_pv_id,
        ];
    }
}
