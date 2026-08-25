<?php

namespace App\Http\Requests\Affaires;

use Illuminate\Foundation\Http\FormRequest;

class EnregistrerScelleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('affaire'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero_scelle' => ['required', 'string', 'max:255', 'unique:scelles,numero_scelle'],
            'description' => ['required', 'string'],
            'lieu_saisie' => ['nullable', 'string', 'max:255'],
        ];
    }
}
