<?php

namespace App\Policies;

use App\Domain\Audiencement\Models\DossierAudiencement;
use App\Models\User;

/**
 * Cloisonnement des habilitations par ressort (§8), sur le ressort de
 * l'affaire liée — même pattern que DossierParquetPolicy et
 * DossierInstructionPolicy.
 */
class DossierAudiencementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('audiencement.gerer');
    }

    public function view(User $user, DossierAudiencement $dossier): bool
    {
        return $this->viewAny($user) && $this->memeRessort($user, $dossier);
    }

    public function gerer(User $user, DossierAudiencement $dossier): bool
    {
        return $this->view($user, $dossier);
    }

    private function memeRessort(User $user, DossierAudiencement $dossier): bool
    {
        if ($user->can('administration.gerer')) {
            return true;
        }

        return $user->ressort_id !== null && $user->ressort_id === $dossier->affaire->ressort_id;
    }
}
