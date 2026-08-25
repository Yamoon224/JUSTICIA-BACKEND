<?php

namespace App\Http\Requests\GardeAVue;

use App\Http\Requests\GardeAVue\Concerns\AutoriseSurRessortDeLaMesure;
use Illuminate\Foundation\Http\FormRequest;

class CloturerGardeAVueRequest extends FormRequest
{
    use AutoriseSurRessortDeLaMesure;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // §6.1 : issue obligatoirement renseignée à la clôture.
            'issue' => ['required', 'in:liberation,convocation,deferement'],
        ];
    }
}
