<?php

namespace App\Http\Requests\Audiencement;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IntegrerDecisionRecoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gerer', $this->route('recours')->decision->dossierAudiencement);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'issue' => [
                'required',
                Rule::in(['confirmation', 'infirmation', 'cassation_avec_renvoi']),
                $this->recoursPeutEtreResolu(...),
            ],
        ];
    }

    private function recoursPeutEtreResolu(string $attribute, mixed $value, Closure $fail): void
    {
        $recours = $this->route('recours');

        if (! $recours->recevable) {
            $fail('Un recours irrecevable ne peut pas recevoir de décision.');
        } elseif ($recours->estResolu()) {
            $fail('Ce recours a déjà reçu une décision.');
        }
    }
}
