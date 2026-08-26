<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;

class AssignerRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('habilitations.gerer');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'roles' => ['present', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }
}
