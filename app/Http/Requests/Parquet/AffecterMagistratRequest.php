<?php

namespace App\Http\Requests\Parquet;

use Illuminate\Foundation\Http\FormRequest;

class AffecterMagistratRequest extends FormRequest
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
            'magistrat_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
