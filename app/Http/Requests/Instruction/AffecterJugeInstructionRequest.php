<?php

namespace App\Http\Requests\Instruction;

use App\Http\Requests\Instruction\Concerns\AutoriseSurRessortDuDossier;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class AffecterJugeInstructionRequest extends FormRequest
{
    use AutoriseSurRessortDuDossier;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'juge_id' => ['required', 'integer', 'exists:users,id', $this->estUnJugeDuRessort(...)],
        ];
    }

    /**
     * §6.6 : seul un juge d'instruction du ressort de l'affaire peut être
     * affecté — même garde que pour l'affectation d'un dossier parquet
     * (§6.5, App\Http\Requests\Parquet\AffecterMagistratRequest).
     */
    private function estUnJugeDuRessort(string $attribute, mixed $value, Closure $fail): void
    {
        $juge = User::query()->find($value);
        $ressortAffaire = $this->route('dossier')->affaire->ressort_id;

        if (! $juge?->hasRole('juge_instruction') || $juge->ressort_id !== $ressortAffaire) {
            $fail('Le juge doit être un juge d\'instruction du ressort de l\'affaire.');
        }
    }
}
