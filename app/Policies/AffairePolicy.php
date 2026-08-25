<?php

namespace App\Policies;

use App\Domain\Affaires\Models\Affaire;
use App\Models\User;

/**
 * Cloisonnement des habilitations par ressort (§8) : « un OPJ ne voit que
 * les affaires de son unité ; un greffier, celles de sa juridiction ».
 * Approximé ici par le ressort de rattachement de l'agent — le raffinement
 * par unité précise viendra avec le module Audiencement/Greffe.
 */
class AffairePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('affaires.gerer') || $user->can('affaires.consulter');
    }

    public function view(User $user, Affaire $affaire): bool
    {
        return $this->viewAny($user) && $this->memeRessort($user, $affaire);
    }

    public function create(User $user): bool
    {
        return $user->can('affaires.gerer');
    }

    public function update(User $user, Affaire $affaire): bool
    {
        return $user->can('affaires.gerer') && $this->memeRessort($user, $affaire);
    }

    private function memeRessort(User $user, Affaire $affaire): bool
    {
        // Un administrateur/chef de juridiction avec supervision explicite
        // n'est pas limité par le ressort d'origine.
        if ($user->can('affaires.superviser') || $user->can('administration.gerer')) {
            return true;
        }

        return $user->ressort_id !== null && $user->ressort_id === $affaire->ressort_id;
    }
}
