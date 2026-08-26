<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;

class SuspendreCompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('administration.gerer');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motif' => ['nullable', 'string', 'max:255'],
        ];
    }
}
