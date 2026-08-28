<?php

namespace App\Http\Requests\Instruction;

use App\Domain\Support\TexteEnrichiSanitizer;
use App\Http\Requests\Instruction\Concerns\AutoriseSurRessortDuDossier;
use App\Http\Requests\Instruction\Concerns\ValidePersonneRattacheeAuDossier;
use Illuminate\Foundation\Http\FormRequest;

class PlacerSousControleJudiciaireRequest extends FormRequest
{
    use AutoriseSurRessortDuDossier;
    use ValidePersonneRattacheeAuDossier;

    protected function prepareForValidation(): void
    {
        $this->merge(['obligations' => TexteEnrichiSanitizer::nettoyer($this->input('obligations'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'personne_id' => ['required', 'integer', 'exists:personnes,id', $this->personneEstPartieAuDossier(...)],
            'obligations' => ['required', 'string'],
        ];
    }
}
