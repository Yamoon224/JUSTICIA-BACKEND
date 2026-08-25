<?php

namespace App\Policies;

use App\Domain\Parquet\Models\DossierParquet;
use App\Models\User;

/**
 * Cloisonnement des habilitations par ressort (§8), sur le ressort de
 * l'affaire liée — le parquet ne voit que les dossiers de son ressort, sauf
 * supervision explicite.
 */
class DossierParquetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('parquet.gerer');
    }

    public function view(User $user, DossierParquet $dossier): bool
    {
        return $this->viewAny($user) && $this->memeRessort($user, $dossier);
    }

    public function gerer(User $user, DossierParquet $dossier): bool
    {
        return $this->view($user, $dossier);
    }

    private function memeRessort(User $user, DossierParquet $dossier): bool
    {
        if ($user->can('administration.gerer')) {
            return true;
        }

        return $user->ressort_id !== null && $user->ressort_id === $dossier->affaire->ressort_id;
    }
}
