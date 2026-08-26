<?php

namespace App\Http\Resources;

use App\Domain\Documents\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Document
 */
class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'categorie' => $this->categorie,
            'cote' => $this->cote,
            'nom_original' => $this->nom_original,
            'mime_type' => $this->mime_type,
            'taille_octets' => $this->taille_octets,
            'hash_integrite' => $this->hash_integrite,
            'verse_par' => $this->verse_par,
            'created_at' => $this->created_at,
        ];
    }
}
