<?php

namespace App\Http\Requests\GardeAVue\Concerns;

/**
 * Un agent n'agit sur une mesure de garde à vue que s'il a la permission
 * `gav.gerer` ET que l'affaire liée relève de son ressort (§8) —
 * AffairePolicy::update porte déjà cette seconde règle, on la réutilise
 * plutôt que de la dupliquer par action.
 */
trait AutoriseSurRessortDeLaMesure
{
    public function authorize(): bool
    {
        return $this->user()->can('gav.gerer')
            && $this->user()->can('update', $this->route('mesure')->affaire);
    }
}
