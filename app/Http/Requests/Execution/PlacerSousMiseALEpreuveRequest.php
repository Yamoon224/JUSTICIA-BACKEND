<?php

namespace App\Http\Requests\Execution;

use App\Http\Requests\Execution\Concerns\AutoriseSurRessortDuDossier;
use Illuminate\Foundation\Http\FormRequest;

class PlacerSousMiseALEpreuveRequest extends FormRequest
{
    use AutoriseSurRessortDuDossier;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'obligations' => ['required', 'string'],
        ];
    }
}
