<?php

namespace App\Http\Requests\Instruction;

use App\Http\Requests\Instruction\Concerns\AutoriseSurRessortDuDossier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RendreOrdonnanceRequest extends FormRequest
{
    use AutoriseSurRessortDuDossier;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ordonnance' => ['required', Rule::in(['renvoi', 'non_lieu'])],
        ];
    }
}
