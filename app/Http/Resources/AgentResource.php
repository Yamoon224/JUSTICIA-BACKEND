<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class AgentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'matricule' => $this->matricule,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'nom_complet' => $this->nomComplet(),
            'email' => $this->email,
            'actif' => $this->actif,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'service' => $this->whenLoaded('service', fn () => [
                'id' => $this->service->id,
                'code' => $this->service->code,
                'nom' => $this->service->nom,
                'type' => $this->service->type,
            ]),
            'ressort' => $this->whenLoaded('ressort', fn () => [
                'id' => $this->ressort->id,
                'code' => $this->ressort->code,
                'nom' => $this->ressort->nom,
                'type' => $this->ressort->type,
            ]),
        ];
    }
}
