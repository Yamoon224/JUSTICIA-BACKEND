<?php

namespace App\Http\Requests\Personnes;

use App\Domain\Personnes\Models\Personne;
use Illuminate\Foundation\Http\FormRequest;

class CreerPersonneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Personne::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:physique,morale'],
            'nom' => ['required_if:type,physique', 'nullable', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'alias' => ['nullable', 'array'],
            'alias.*' => ['string', 'max:255'],
            'date_naissance' => ['nullable', 'date'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'sexe' => ['nullable', 'in:M,F'],
            'nom_pere' => ['nullable', 'string', 'max:255'],
            'nom_mere' => ['nullable', 'string', 'max:255'],
            'raison_sociale' => ['required_if:type,morale', 'nullable', 'string', 'max:255'],
            'representant_legal_id' => ['nullable', 'exists:personnes,id'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'signalement' => ['nullable', 'string', 'max:255'],
        ];
    }
}
