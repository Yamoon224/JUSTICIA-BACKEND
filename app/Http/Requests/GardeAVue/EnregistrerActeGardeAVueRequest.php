<?php

namespace App\Http\Requests\GardeAVue;

use App\Domain\Support\TexteEnrichiSanitizer;
use App\Http\Requests\GardeAVue\Concerns\AutoriseSurRessortDeLaMesure;
use Illuminate\Foundation\Http\FormRequest;

class EnregistrerActeGardeAVueRequest extends FormRequest
{
    use AutoriseSurRessortDeLaMesure;

    protected function prepareForValidation(): void
    {
        $this->merge(['notes' => TexteEnrichiSanitizer::nettoyer($this->input('notes'))]);
    }

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
