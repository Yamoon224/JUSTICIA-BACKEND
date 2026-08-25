<?php

namespace App\Http\Requests\Parquet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrienterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gerer', $this->route('dossier'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'orientation' => ['required', Rule::in([
                'classement_sans_suite',
                'rappel_a_la_loi',
                'mediation_penale',
                'composition_penale',
                'citation_directe',
                'ouverture_information',
                'comparution_immediate',
            ])],
            // §6.5 : un classement sans suite exige toujours un motif.
            'motif_classement_id' => [
                Rule::requiredIf($this->input('orientation') === 'classement_sans_suite'),
                'nullable', 'integer', 'exists:motifs_classement,id',
            ],
        ];
    }
}
