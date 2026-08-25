<?php

namespace App\Http\Requests\Execution;

use App\Http\Requests\Execution\Concerns\AutoriseSurRessortDuDossier;
use Illuminate\Foundation\Http\FormRequest;

class EcrouerRequest extends FormRequest
{
    use AutoriseSurRessortDuDossier;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'etablissement_id' => ['required', 'integer', 'exists:etablissements_penitentiaires,id'],
            'duree_jours' => ['required', 'integer', 'min:1'],
            'detention_provisoire_imputee_jours' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
