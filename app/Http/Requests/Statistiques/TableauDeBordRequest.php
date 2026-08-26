<?php

namespace App\Http\Requests\Statistiques;

use Illuminate\Foundation\Http\FormRequest;

class TableauDeBordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('statistiques.consulter') || $this->user()->can('administration.gerer');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Ignoré pour un agent non administrateur : voir
            // TableauDeBordController — il reste toujours cantonné à son
            // propre ressort (§8), quoi qu'il envoie ici.
            'ressort_id' => ['nullable', 'integer', 'exists:ressorts,id'],
        ];
    }
}
