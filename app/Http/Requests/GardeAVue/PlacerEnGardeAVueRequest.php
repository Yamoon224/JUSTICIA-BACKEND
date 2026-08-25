<?php

namespace App\Http\Requests\GardeAVue;

use App\Domain\Affaires\Models\Affaire;
use Illuminate\Foundation\Http\FormRequest;

class PlacerEnGardeAVueRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->can('gav.gerer')) {
            return false;
        }

        // §8 : un OPJ ne place en garde à vue que sur les affaires de son
        // ressort — sans ce contrôle, l'affaire_id fourni permettrait à
        // n'importe quel agent d'agir sur le dossier d'un autre ressort.
        $affaire = Affaire::query()->find($this->integer('affaire_id'));

        return $affaire !== null && $this->user()->can('update', $affaire);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'affaire_id' => ['required', 'integer', 'exists:affaires,id'],
            'personne_id' => ['required', 'integer', 'exists:personnes,id'],
            'unite_id' => ['required', 'integer', 'exists:unites,id'],
            'debut_at' => ['nullable', 'date'],
        ];
    }
}
