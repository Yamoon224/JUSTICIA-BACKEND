<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Référentiel légal des infractions, versionné (§6.13, §11) : les réformes
 * du code de procédure pénale s'intègrent par une nouvelle version avec date
 * d'effet, jamais par modification silencieuse d'une entrée existante.
 */
#[Fillable(['code', 'libelle', 'categorie', 'texte_reference', 'version', 'date_entree_vigueur', 'date_fin_vigueur'])]
class Infraction extends Model
{
    protected function casts(): array
    {
        return [
            'date_entree_vigueur' => 'date',
            'date_fin_vigueur' => 'date',
        ];
    }

    public function estEnVigueur(): bool
    {
        $today = now()->toDateString();

        return $this->date_entree_vigueur->toDateString() <= $today
            && (! $this->date_fin_vigueur || $this->date_fin_vigueur->toDateString() >= $today);
    }
}
