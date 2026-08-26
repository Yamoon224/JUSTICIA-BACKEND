<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;

class CreerCompteRequest extends FormRequest
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
            'matricule' => ['required', 'string', 'max:255', 'unique:users,matricule'],
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'ressort_id' => ['nullable', 'integer', 'exists:ressorts,id'],
        ];
    }
}
