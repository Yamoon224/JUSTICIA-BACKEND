<?php

namespace App\Http\Requests\Audiencement;

use App\Http\Requests\Audiencement\Concerns\AutoriseSurRessortDuDossier;
use App\Http\Requests\Audiencement\Concerns\ValidePersonneRattacheeAuDossier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnregistrerDecisionRequest extends FormRequest
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
            'decision' => ['required', Rule::in(['condamnation', 'relaxe', 'acquittement', 'dispense_de_peine'])],
            'peine_principale' => ['nullable', 'string', 'max:255'],
            'sursis' => ['boolean'],
            'interets_civils' => ['nullable', 'string'],
        ];
    }
}
