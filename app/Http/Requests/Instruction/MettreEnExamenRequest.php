<?php

namespace App\Http\Requests\Instruction;

use App\Http\Requests\Instruction\Concerns\AutoriseSurRessortDuDossier;
use App\Http\Requests\Instruction\Concerns\ValidePersonneRattacheeAuDossier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MettreEnExamenRequest extends FormRequest
{
    use AutoriseSurRessortDuDossier;
    use ValidePersonneRattacheeAuDossier;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'personne_id' => ['required', 'integer', 'exists:personnes,id', $this->personneEstPartieAuDossier(...)],
            'statut' => ['required', Rule::in(['mis_en_examen', 'temoin_assiste'])],
        ];
    }
}
