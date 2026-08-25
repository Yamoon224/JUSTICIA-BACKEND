<?php

namespace App\Http\Requests\GardeAVue;

use App\Http\Requests\GardeAVue\Concerns\AutoriseSurRessortDeLaMesure;
use Illuminate\Foundation\Http\FormRequest;

class NotifierDroitRequest extends FormRequest
{
    use AutoriseSurRessortDeLaMesure;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'droit' => ['required', 'in:silence,avocat,medecin,information_proche'],
            'mode_de_remise' => ['required', 'string', 'max:255'],
        ];
    }
}
