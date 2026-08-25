<?php

namespace App\Http\Requests\Personnes;

use App\Domain\Personnes\Models\Personne;
use Illuminate\Foundation\Http\FormRequest;

class RechercherPersonnesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Personne::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nom' => ['nullable', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'date_naissance' => ['nullable', 'date'],
        ];
    }
}
