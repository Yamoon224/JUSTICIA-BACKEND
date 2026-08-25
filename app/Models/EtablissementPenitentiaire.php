<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['code', 'nom', 'ressort_id', 'capacite'])]
class EtablissementPenitentiaire extends Model
{
    // La pluralisation par défaut d'Eloquent donnerait
    // "etablissement_penitentiaires" (un seul "s").
    protected $table = 'etablissements_penitentiaires';

    /**
     * @return BelongsTo<Ressort, $this>
     */
    public function ressort(): BelongsTo
    {
        return $this->belongsTo(Ressort::class);
    }
}
