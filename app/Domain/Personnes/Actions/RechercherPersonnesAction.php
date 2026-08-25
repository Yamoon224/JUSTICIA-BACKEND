<?php

namespace App\Domain\Personnes\Actions;

use App\Domain\Personnes\Models\Personne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Recherche multicritère (§6.2) : nom, prénom, date de naissance, alias.
 * Utilisée aussi bien pour la saisie courante que pour la détection de
 * doublons — le rapprochement proposé reste ensuite une décision humaine
 * (FusionnerPersonnesAction), jamais automatique.
 */
class RechercherPersonnesAction
{
    public function executer(?string $nom, ?string $prenom, ?string $dateNaissance): LengthAwarePaginator
    {
        return Personne::query()
            ->whereNull('fusionnee_dans_id')
            ->when($nom, fn (Builder $query) => $query->where(function (Builder $q) use ($nom) {
                $q->where('nom', 'like', "%{$nom}%")
                    ->orWhere('raison_sociale', 'like', "%{$nom}%")
                    ->orWhereJsonContains('alias', $nom);
            }))
            ->when($prenom, fn (Builder $query) => $query->where('prenom', 'like', "%{$prenom}%"))
            ->when($dateNaissance, fn (Builder $query) => $query->whereDate('date_naissance', $dateNaissance))
            ->orderBy('nom')
            ->paginate(25);
    }
}
