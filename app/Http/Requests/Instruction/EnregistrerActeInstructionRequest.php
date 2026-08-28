<?php

namespace App\Http\Requests\Instruction;

use App\Domain\Support\TexteEnrichiSanitizer;
use App\Http\Requests\Instruction\Concerns\AutoriseSurRessortDuDossier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnregistrerActeInstructionRequest extends FormRequest
{
    use AutoriseSurRessortDuDossier;

    protected function prepareForValidation(): void
    {
        $this->merge(['description' => TexteEnrichiSanitizer::nettoyer($this->input('description'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['interrogatoire', 'confrontation', 'transport', 'commission_rogatoire', 'expertise'])],
            'description' => ['nullable', 'string'],
            'date_prevue' => ['nullable', 'date'],
        ];
    }
}
