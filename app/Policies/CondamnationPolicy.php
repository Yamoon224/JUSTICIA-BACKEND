<?php

namespace App\Policies;

use App\Domain\Casier\Models\Condamnation;
use App\Models\User;

/**
 * Le casier judiciaire est un registre national (§6.10), à la différence du
 * reste du socle : pas de cloisonnement par ressort ici (une condamnation
 * prononcée dans un ressort doit rester visible/gérable depuis n'importe
 * quel autre), seulement la permission `casier.gerer` — réservée à la
 * gestion des mentions (réhabilitation judiciaire, amnistie), distincte de
 * `casier.consulter_nominatif` qui gouverne la génération d'un bulletin
 * (voir App\Http\Requests\Casier\GenererBulletinRequest).
 */
class CondamnationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('casier.gerer');
    }

    public function view(User $user, Condamnation $condamnation): bool
    {
        return $this->viewAny($user);
    }

    public function gerer(User $user, Condamnation $condamnation): bool
    {
        return $this->viewAny($user);
    }
}
