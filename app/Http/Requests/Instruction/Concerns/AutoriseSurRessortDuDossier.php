<?php

namespace App\Http\Requests\Instruction\Concerns;

/**
 * Un agent n'agit sur un dossier d'instruction que si DossierInstructionPolicy
 * l'autorise — permission `instruction.gerer` ET ressort de l'affaire liée
 * (§8). Ne réutilise volontairement pas AffairePolicy::update, qui exige la
 * permission `affaires.gerer` (celle des OPJ, pas des juges d'instruction).
 */
trait AutoriseSurRessortDuDossier
{
    public function authorize(): bool
    {
        return $this->user()->can('gerer', $this->route('dossier'));
    }
}
