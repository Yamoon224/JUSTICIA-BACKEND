<?php

namespace App\Http\Requests\Affaires;

use Illuminate\Foundation\Http\FormRequest;

class SignerProcesVerbalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('procesVerbal')->affaire);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
