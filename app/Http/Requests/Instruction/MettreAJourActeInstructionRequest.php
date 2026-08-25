<?php

namespace App\Http\Requests\Instruction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MettreAJourActeInstructionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gerer', $this->route('acte')->dossierInstruction);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'statut' => ['required', Rule::in(['realise', 'retour_recu', 'rapport_depose'])],
        ];
    }
}
