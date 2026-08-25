<?php

namespace App\Http\Requests\GardeAVue;

use Illuminate\Foundation\Http\FormRequest;

class PlacerEnGardeAVueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gav.gerer');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'affaire_id' => ['required', 'integer', 'exists:affaires,id'],
            'personne_id' => ['required', 'integer', 'exists:personnes,id'],
            'unite_id' => ['required', 'integer', 'exists:unites,id'],
            'debut_at' => ['nullable', 'date'],
        ];
    }
}
