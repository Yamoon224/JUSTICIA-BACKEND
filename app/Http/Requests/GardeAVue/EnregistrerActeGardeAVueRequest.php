<?php

namespace App\Http\Requests\GardeAVue;

use App\Http\Requests\GardeAVue\Concerns\AutoriseSurRessortDeLaMesure;
use Illuminate\Foundation\Http\FormRequest;

class EnregistrerActeGardeAVueRequest extends FormRequest
{
    use AutoriseSurRessortDeLaMesure;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:audition,examen_medical,entretien_avocat,confrontation,repos'],
            'debut_at' => ['required', 'date'],
            'fin_at' => ['nullable', 'date', 'after_or_equal:debut_at'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
