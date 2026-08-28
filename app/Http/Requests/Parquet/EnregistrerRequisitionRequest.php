<?php

namespace App\Http\Requests\Parquet;

use App\Domain\Support\TexteEnrichiSanitizer;
use Illuminate\Foundation\Http\FormRequest;

class EnregistrerRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gerer', $this->route('dossier'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['contenu' => TexteEnrichiSanitizer::nettoyer($this->input('contenu'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:255'],
            'contenu' => ['required', 'string'],
        ];
    }
}
