<?php

namespace App\Http\Requests\Instruction;

use Illuminate\Foundation\Http\FormRequest;

class RenouvelerDetentionProvisoireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gerer', $this->route('mesure')->dossierInstruction);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'jours' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }
}
