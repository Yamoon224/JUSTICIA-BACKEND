<?php

namespace App\Http\Requests\Affaires;

use App\Domain\Affaires\Models\Affaire;
use Illuminate\Foundation\Http\FormRequest;

class OuvrirAffaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Affaire::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'unite_id' => ['nullable', 'exists:unites,id'],
            'description' => ['nullable', 'string'],
            'date_ouverture' => ['nullable', 'date'],
            'infractions' => ['nullable', 'array'],
            'infractions.*' => ['integer', 'exists:infractions,id'],
        ];
    }
}
