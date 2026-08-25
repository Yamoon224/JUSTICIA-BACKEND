<?php

namespace App\Http\Requests\Instruction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MettreAJourMandatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gerer', $this->route('mandat')->dossierInstruction);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'etape' => ['required', Rule::in(['diffuse', 'execute'])],
        ];
    }
}
