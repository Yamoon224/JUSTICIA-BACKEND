<?php

namespace App\Http\Requests\Casier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * §6.10 : la génération d'un bulletin est une consultation nominative,
 * gouvernée par la permission dédiée `casier.consulter_nominatif` — plus
 * stricte que `casier.gerer` (un greffier peut par exemple enregistrer une
 * réhabilitation judiciaire connue sans pouvoir consulter librement le
 * casier de n'importe qui, cf. RolesEtPermissionsSeeder).
 */
class GenererBulletinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('casier.consulter_nominatif');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['b1', 'b2', 'b3'])],
            'motif' => ['required', 'string', 'max:255'],
        ];
    }
}
