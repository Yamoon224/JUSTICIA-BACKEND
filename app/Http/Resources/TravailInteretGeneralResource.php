<?php

namespace App\Http\Resources;

use App\Domain\Execution\Models\TravailInteretGeneral;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TravailInteretGeneral
 */
class TravailInteretGeneralResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'heures_requises' => $this->heures_requises,
            'heures_effectuees' => $this->heures_effectuees,
            'affecte_a' => $this->affecte_a,
            'statut' => $this->statut,
        ];
    }
}
