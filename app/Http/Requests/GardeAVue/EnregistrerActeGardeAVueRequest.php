<?php

namespace App\Http\Requests\GardeAVue;

use Illuminate\Foundation\Http\FormRequest;

class EnregistrerActeGardeAVueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gav.gerer');
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
