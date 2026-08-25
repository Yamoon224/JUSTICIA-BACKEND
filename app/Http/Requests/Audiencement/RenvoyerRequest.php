<?php

namespace App\Http\Requests\Audiencement;

use App\Http\Requests\Audiencement\Concerns\AutoriseSurRessortDuDossier;
use Illuminate\Foundation\Http\FormRequest;

class RenvoyerRequest extends FormRequest
{
    use AutoriseSurRessortDuDossier;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nouvelle_date' => ['required', 'date', 'after:now'],
            'motif' => ['required', 'string', 'max:255'],
        ];
    }
}
