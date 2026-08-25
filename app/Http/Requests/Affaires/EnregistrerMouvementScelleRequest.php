<?php

namespace App\Http\Requests\Affaires;

use Illuminate\Foundation\Http\FormRequest;

class EnregistrerMouvementScelleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('scelle')->affaire);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:sortie_expertise,retour_expertise,restitution,confiscation,destruction'],
            'remettant_id' => ['nullable', 'integer', 'exists:users,id'],
            'recepteur_id' => ['nullable', 'integer', 'exists:users,id'],
            'motif' => ['nullable', 'string', 'max:255'],
        ];
    }
}
