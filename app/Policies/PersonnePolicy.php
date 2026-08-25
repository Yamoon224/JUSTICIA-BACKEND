<?php

namespace App\Policies;

use App\Models\User;

/**
 * Le fichier des personnes est un référentiel national (§6.2) : il n'est pas
 * cloisonné par ressort comme les affaires (§8), mais l'accès reste
 * conditionné aux permissions du profil, et toute consultation individuelle
 * reste journalisée avec motif (ConsulterPersonneAction) — ce n'est donc pas
 * l'absence de contrôle, mais un contrôle par la traçabilité plutôt que par
 * le périmètre géographique.
 */
class PersonnePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('personnes.gerer') || $user->can('personnes.consulter');
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('personnes.gerer');
    }

    public function update(User $user): bool
    {
        return $user->can('personnes.gerer');
    }

    public function fusionner(User $user): bool
    {
        return $user->can('personnes.gerer');
    }
}
