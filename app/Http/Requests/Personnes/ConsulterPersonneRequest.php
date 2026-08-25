<?php

namespace App\Http\Requests\Personnes;

use Illuminate\Foundation\Http\FormRequest;

class ConsulterPersonneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('personne'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // §6.2 : toute consultation est journalisée avec motif — non
            // optionnel, contrairement à un simple log applicatif.
            'motif' => ['required', 'string', 'max:255'],
        ];
    }
}
