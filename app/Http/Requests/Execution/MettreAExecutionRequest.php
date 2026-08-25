<?php

namespace App\Http\Requests\Execution;

use Illuminate\Foundation\Http\FormRequest;

class MettreAExecutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $decision = $this->route('decision');

        if (! $this->user()->can('execution.gerer')) {
            return false;
        }

        return $this->user()->can('administration.gerer')
            || $this->user()->ressort_id === $decision->dossierAudiencement->affaire->ressort_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
