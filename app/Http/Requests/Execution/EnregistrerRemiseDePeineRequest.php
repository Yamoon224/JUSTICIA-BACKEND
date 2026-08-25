<?php

namespace App\Http\Requests\Execution;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnregistrerRemiseDePeineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gerer', $this->route('ecrou')->dossierExecution);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'jours' => ['required', 'integer', 'min:1'],
            'motif' => ['required', Rule::in(['grace', 'reduction_peine'])],
        ];
    }
}
