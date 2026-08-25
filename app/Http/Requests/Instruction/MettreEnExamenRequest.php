<?php

namespace App\Http\Requests\Instruction;

use App\Http\Requests\Instruction\Concerns\AutoriseSurRessortDuDossier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MettreEnExamenRequest extends FormRequest
{
    use AutoriseSurRessortDuDossier;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'personne_id' => ['required', 'integer', 'exists:personnes,id'],
            'statut' => ['required', Rule::in(['mis_en_examen', 'temoin_assiste'])],
        ];
    }
}
