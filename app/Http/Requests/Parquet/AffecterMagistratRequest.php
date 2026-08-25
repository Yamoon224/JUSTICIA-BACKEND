<?php

namespace App\Http\Requests\Parquet;

use App\Models\User;
use Closure;
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
            'magistrat_id' => ['required', 'integer', 'exists:users,id', $this->estUnMagistratDuRessort(...)],
        ];
    }

    /**
     * §6.5 : seul un procureur du ressort de l'affaire peut être affecté —
     * empêche d'assigner un dossier à n'importe quel compte valide
     * (agent d'un autre profil ou d'un autre ressort).
     */
    private function estUnMagistratDuRessort(string $attribute, mixed $value, Closure $fail): void
    {
        $magistrat = User::query()->find($value);
        $ressortAffaire = $this->route('dossier')->affaire->ressort_id;

        if (! $magistrat?->hasRole('procureur') || $magistrat->ressort_id !== $ressortAffaire) {
            $fail('Le magistrat doit être un procureur du ressort de l\'affaire.');
        }
    }
}
