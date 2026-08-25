<?php

namespace App\Http\Requests\Audiencement;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnregistrerRecoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gerer', $this->route('decision')->dossierAudiencement);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['appel', 'opposition', 'pourvoi_cassation'])],
            'formee_par_personne_id' => ['nullable', 'integer', 'exists:personnes,id', $this->personneEstPartieAlAffaire(...)],
        ];
    }

    /**
     * §6.2, §6.8 : un recours ne peut être formé, le cas échéant, que par
     * une personne déjà partie à l'affaire — même garde que pour une
     * décision ou une mise en examen.
     */
    private function personneEstPartieAlAffaire(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $affaire = $this->route('decision')->dossierAudiencement->affaire;

        if (! $affaire->personnes()->whereKey($value)->exists()) {
            $fail('Cette personne doit être partie à l\'affaire.');
        }
    }
}
