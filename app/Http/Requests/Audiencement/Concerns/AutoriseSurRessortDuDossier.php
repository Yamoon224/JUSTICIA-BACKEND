<?php

namespace App\Http\Requests\Audiencement\Concerns;

/**
 * Même principe que les traits homonymes de Parquet/Instruction :
 * permission `audiencement.gerer` ET ressort de l'affaire liée (§8), via
 * DossierAudiencementPolicy plutôt qu'AffairePolicy::update (qui exige
 * `affaires.gerer`, celle des OPJ).
 */
trait AutoriseSurRessortDuDossier
{
    public function authorize(): bool
    {
        return $this->user()->can('gerer', $this->route('dossier'));
    }
}
