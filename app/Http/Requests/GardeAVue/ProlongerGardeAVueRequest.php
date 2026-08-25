<?php

namespace App\Http\Requests\GardeAVue;

use App\Http\Requests\GardeAVue\Concerns\AutoriseSurRessortDeLaMesure;
use Illuminate\Foundation\Http\FormRequest;

class ProlongerGardeAVueRequest extends FormRequest
{
    // La prolongation est demandée par l'OPJ mais l'autorisation reste
    // celle du parquet (§6.1) : voir `autorise_par_id` ci-dessous et
    // App\Domain\GardeAVue\Actions\ProlongerGardeAVueAction.
    use AutoriseSurRessortDeLaMesure;

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
