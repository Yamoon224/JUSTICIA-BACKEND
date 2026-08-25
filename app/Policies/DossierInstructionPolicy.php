<?php

namespace App\Policies;

use App\Domain\Instruction\Models\DossierInstruction;
use App\Models\User;

/**
 * Cloisonnement des habilitations par ressort (§8), sur le ressort de
 * l'affaire liée — un juge d'instruction ne voit que les dossiers de son
 * ressort, sauf supervision explicite. Volontairement indépendante
 * d'AffairePolicy::update, qui exige la permission `affaires.gerer` (celle
 * des OPJ) — un juge d'instruction n'agit ici que via `instruction.gerer`.
 */
class DossierInstructionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('instruction.gerer');
    }

    public function view(User $user, DossierInstruction $dossier): bool
    {
        return $this->viewAny($user) && $this->memeRessort($user, $dossier);
    }

    public function gerer(User $user, DossierInstruction $dossier): bool
    {
        return $this->view($user, $dossier);
    }

    private function memeRessort(User $user, DossierInstruction $dossier): bool
    {
        if ($user->can('administration.gerer')) {
            return true;
        }

        return $user->ressort_id !== null && $user->ressort_id === $dossier->affaire->ressort_id;
    }
}
