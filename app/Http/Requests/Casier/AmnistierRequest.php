<?php

namespace App\Http\Requests\Casier;

use Illuminate\Foundation\Http\FormRequest;

class AmnistierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gerer', $this->route('condamnation'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'texte_reference' => ['required', 'string', 'max:255'],
        ];
    }
}
