<?php

namespace App\Http\Requests\GardeAVue;

use Illuminate\Foundation\Http\FormRequest;

class ProlongerGardeAVueRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La prolongation est demandée par l'OPJ mais l'autorisation reste
        // celle du parquet (§6.1) : voir `autorise_par_id` ci-dessous et
        // App\Domain\GardeAVue\Actions\ProlongerGardeAVueAction.
        return $this->user()->can('gav.gerer');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'heures' => ['required', 'integer', 'min:1', 'max:96'],
            'autorise_par_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
