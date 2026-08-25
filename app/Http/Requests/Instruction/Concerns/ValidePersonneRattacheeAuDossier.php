<?php

namespace App\Http\Requests\Instruction\Concerns;

use Closure;

/**
 * §6.2, §6.6 : mise en examen, mandat, contrôle judiciaire ou détention
 * provisoire ne peuvent viser qu'une personne déjà partie à l'affaire du
 * dossier (rattachée via affaire_personne) — sans cette garde, un
 * `personne_id` valide mais étranger à l'affaire (n'importe quelle fiche du
 * référentiel national des personnes, §6.2) pourrait être visé par erreur
 * ou abus.
 */
trait ValidePersonneRattacheeAuDossier
{
    protected function personneEstPartieAuDossier(string $attribute, mixed $value, Closure $fail): void
    {
        $dossier = $this->route('dossier');

        if (! $dossier->affaire->personnes()->whereKey($value)->exists()) {
            $fail('Cette personne doit d\'abord être rattachée à l\'affaire.');
        }
    }
}
