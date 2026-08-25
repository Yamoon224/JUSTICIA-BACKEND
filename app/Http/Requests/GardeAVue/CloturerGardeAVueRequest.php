<?php

namespace App\Http\Requests\GardeAVue;

use Illuminate\Foundation\Http\FormRequest;

class CloturerGardeAVueRequest extends FormRequest
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
            // §6.1 : issue obligatoirement renseignée à la clôture.
            'issue' => ['required', 'in:liberation,convocation,deferement'],
        ];
    }
}
