<?php

namespace App\Http\Requests\Instruction;

use App\Http\Requests\Instruction\Concerns\AutoriseSurRessortDuDossier;
use Illuminate\Foundation\Http\FormRequest;

class PlacerSousControleJudiciaireRequest extends FormRequest
{
    use AutoriseSurRessortDuDossier;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'personne_id' => ['required', 'integer', 'exists:personnes,id'],
            'obligations' => ['required', 'string'],
        ];
    }
}
