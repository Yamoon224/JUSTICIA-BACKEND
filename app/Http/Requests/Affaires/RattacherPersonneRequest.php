<?php

namespace App\Http\Requests\Affaires;

use Illuminate\Foundation\Http\FormRequest;

class RattacherPersonneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('affaire'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'personne_id' => ['required', 'integer', 'exists:personnes,id'],
            'statut' => ['required', 'in:suspect,temoin,temoin_assiste,mis_en_examen,prevenu,accuse,condamne,relaxe,acquitte,non_lieu,victime,avocat_constitue'],
        ];
    }
}
