<?php

namespace App\Http\Requests\Audiencement\Concerns;

use Closure;

/**
 * §6.2, §6.7 : une décision ne peut viser qu'une personne déjà partie à
 * l'affaire du dossier (rattachée via affaire_personne) — même garde que
 * pour la mise en examen (§6.6,
 * App\Http\Requests\Instruction\Concerns\ValidePersonneRattacheeAuDossier).
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
