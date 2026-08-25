<?php

namespace App\Http\Requests\Personnes;

use Illuminate\Foundation\Http\FormRequest;

class FusionnerPersonnesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fusionner', $this->route('personne'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // L'égalité avec la personne conservée est rejetée par
            // FusionnerPersonnesAction (pas une règle de validation de
            // forme : c'est une invariance métier).
            'personne_absorbee_id' => ['required', 'integer', 'exists:personnes,id'],
        ];
    }
}
