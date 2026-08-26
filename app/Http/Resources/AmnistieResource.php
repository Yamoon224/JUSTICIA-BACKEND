<?php

namespace App\Http\Resources;

use App\Domain\Casier\Models\Amnistie;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Amnistie
 */
class AmnistieResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'texte_reference' => $this->texte_reference,
            'decidee_at' => $this->decidee_at->toIso8601String(),
        ];
    }
}
