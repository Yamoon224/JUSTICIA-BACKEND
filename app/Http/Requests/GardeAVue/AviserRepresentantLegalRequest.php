<?php

namespace App\Http\Requests\GardeAVue;

use App\Http\Requests\GardeAVue\Concerns\AutoriseSurRessortDeLaMesure;
use Illuminate\Foundation\Http\FormRequest;

class AviserRepresentantLegalRequest extends FormRequest
{
    use AutoriseSurRessortDeLaMesure;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
