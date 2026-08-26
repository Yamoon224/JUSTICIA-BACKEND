<?php

namespace App\Http\Requests\Documents;

use Illuminate\Foundation\Http\FormRequest;

class VerserDocumentPersonneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('personne'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fichier' => ['required', 'file', 'max:15360', 'mimes:jpg,jpeg,png,webp,pdf'],
            'categorie' => ['required', 'in:photo,piece_identite'],
        ];
    }
}
