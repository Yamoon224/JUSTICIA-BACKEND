<?php

namespace App\Http\Requests\Audiencement;

use App\Http\Requests\Audiencement\Concerns\AutoriseSurRessortDuDossier;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class EnrolerRequest extends FormRequest
{
    use AutoriseSurRessortDuDossier;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'juridiction_id' => ['required', 'integer', 'exists:juridictions,id'],
            'chambre' => ['required', 'string', 'max:255'],
            'date_audience' => ['required', 'date'],
            'president_id' => ['required', 'integer', 'exists:users,id', $this->estUnAgentDuRessort('juge_audience')],
            'greffier_id' => ['required', 'integer', 'exists:users,id', $this->estUnAgentDuRessort('greffier')],
        ];
    }

    /**
     * §6.7 : le président et le greffier composant l'audience doivent
     * relever du ressort de l'affaire — même garde que pour l'affectation
     * d'un dossier parquet ou d'instruction (§6.5, §6.6).
     */
    private function estUnAgentDuRessort(string $role): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($role): void {
            $agent = User::query()->find($value);
            $ressortAffaire = $this->route('dossier')->affaire->ressort_id;

            if (! $agent?->hasRole($role) || $agent->ressort_id !== $ressortAffaire) {
                $fail("Cet agent doit avoir le rôle {$role} dans le ressort de l'affaire.");
            }
        };
    }
}
