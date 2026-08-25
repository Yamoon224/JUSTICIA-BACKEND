<?php

namespace App\Http\Requests\GardeAVue;

use Illuminate\Foundation\Http\FormRequest;

class AviserRepresentantLegalRequest extends FormRequest
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
        return [];
    }
}
