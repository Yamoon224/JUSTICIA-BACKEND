<?php

namespace App\Http\Requests\GardeAVue;

use Illuminate\Foundation\Http\FormRequest;

class NotifierDroitRequest extends FormRequest
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
            'droit' => ['required', 'in:silence,avocat,medecin,information_proche'],
            'mode_de_remise' => ['required', 'string', 'max:255'],
        ];
    }
}
