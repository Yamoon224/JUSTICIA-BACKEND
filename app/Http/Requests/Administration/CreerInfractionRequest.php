<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreerInfractionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('administration.gerer');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255'],
            'libelle' => ['required', 'string', 'max:255'],
            'categorie' => ['required', Rule::in(['contravention', 'delit', 'crime'])],
            'texte_reference' => ['nullable', 'string', 'max:255'],
            'date_entree_vigueur' => ['required', 'date'],
            'date_fin_vigueur' => ['nullable', 'date', 'after:date_entree_vigueur'],
        ];
    }
}
