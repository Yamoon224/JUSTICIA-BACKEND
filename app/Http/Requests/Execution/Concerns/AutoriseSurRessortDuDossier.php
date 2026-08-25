<?php

namespace App\Http\Requests\Execution\Concerns;

/**
 * Même principe que les traits homonymes de Parquet/Instruction/Audiencement :
 * permission `execution.gerer` ET ressort de l'affaire d'origine (§8), via
 * DossierExecutionPolicy.
 */
trait AutoriseSurRessortDuDossier
{
    public function authorize(): bool
    {
        return $this->user()->can('gerer', $this->route('dossier'));
    }
}
