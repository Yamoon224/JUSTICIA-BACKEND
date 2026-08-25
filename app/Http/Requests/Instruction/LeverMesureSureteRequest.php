<?php

namespace App\Http\Requests\Instruction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeverMesureSureteRequest extends FormRequest
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
            'motif' => ['required', Rule::in(['mise_en_liberte', 'echeance'])],
        ];
    }
}
