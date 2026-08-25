<?php

namespace App\Policies;

use App\Domain\Execution\Models\DossierExecution;
use App\Models\User;

/**
 * Cloisonnement des habilitations par ressort (§8), sur le ressort de
 * l'affaire d'origine — même pattern que les policies Parquet, Instruction
 * et Audiencement.
 */
class DossierExecutionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('execution.gerer');
    }

    public function view(User $user, DossierExecution $dossier): bool
    {
        return $this->viewAny($user) && $this->memeRessort($user, $dossier);
    }

    public function gerer(User $user, DossierExecution $dossier): bool
    {
        return $this->view($user, $dossier);
    }

    private function memeRessort(User $user, DossierExecution $dossier): bool
    {
        if ($user->can('administration.gerer')) {
            return true;
        }

        return $user->ressort_id !== null
            && $user->ressort_id === $dossier->decision->dossierAudiencement->affaire->ressort_id;
    }
}
