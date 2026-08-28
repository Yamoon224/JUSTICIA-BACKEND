<?php

namespace App\Http\Requests\Affaires;

use App\Domain\Support\TexteEnrichiSanitizer;
use Illuminate\Foundation\Http\FormRequest;

class RedigerProcesVerbalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('affaire'));
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
            'type' => ['required', 'in:interpellation,audition,perquisition,constatation,autre'],
            'contenu' => ['required', 'string'],
        ];
    }
}
