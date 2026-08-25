<?php

namespace App\Http\Requests\Execution;

use App\Http\Requests\Execution\Concerns\AutoriseSurRessortDuDossier;
use Illuminate\Foundation\Http\FormRequest;

class AffecterTigRequest extends FormRequest
{
    use AutoriseSurRessortDuDossier;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'heures_requises' => ['required', 'integer', 'min:1'],
            'affecte_a' => ['nullable', 'string', 'max:255'],
        ];
    }
}
