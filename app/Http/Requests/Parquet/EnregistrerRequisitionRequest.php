<?php

namespace App\Http\Requests\Parquet;

use Illuminate\Foundation\Http\FormRequest;

class EnregistrerRequisitionRequest extends FormRequest
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
            'type' => ['required', 'string', 'max:255'],
            'contenu' => ['required', 'string'],
        ];
    }
}
