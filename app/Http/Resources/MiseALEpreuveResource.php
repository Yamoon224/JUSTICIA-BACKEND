<?php

namespace App\Http\Resources;

use App\Domain\Execution\Models\MiseALEpreuve;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MiseALEpreuve
 */
class MiseALEpreuveResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'obligations' => $this->obligations,
            'statut' => $this->statut,
        ];
    }
}
