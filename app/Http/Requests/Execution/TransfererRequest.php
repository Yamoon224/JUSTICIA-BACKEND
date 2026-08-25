<?php

namespace App\Http\Requests\Execution;

use Illuminate\Foundation\Http\FormRequest;

class TransfererRequest extends FormRequest
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
            'etablissement_destination_id' => ['required', 'integer', 'exists:etablissements_penitentiaires,id'],
            'motif' => ['nullable', 'string', 'max:255'],
        ];
    }
}
