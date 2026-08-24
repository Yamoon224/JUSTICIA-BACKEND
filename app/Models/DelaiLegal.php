<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Paramétrage d'un délai légal (§6.11, §9) consommé par le moteur central
 * des délais (App\Domain\Delais, à implémenter en Phase 3) : chaque acte
 * générateur crée automatiquement les échéances applicables à partir de ces
 * entrées, plutôt que d'un calcul codé en dur.
 */
#[Fillable([
    'code', 'libelle', 'type_acte', 'categorie_infraction',
    'duree_heures', 'duree_jours', 'alerte_avant_heures', 'alerte_avant_minutes',
    'date_entree_vigueur', 'date_fin_vigueur',
])]
class DelaiLegal extends Model
{
    protected function casts(): array
    {
        return [
            'date_entree_vigueur' => 'date',
            'date_fin_vigueur' => 'date',
        ];
    }
}
