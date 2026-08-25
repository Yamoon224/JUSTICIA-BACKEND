<?php

namespace App\Domain\Execution\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dossier_execution_id', 'obligations', 'statut'])]
class MiseALEpreuve extends Model
{
    // La pluralisation par défaut d'Eloquent ne s'applique pas correctement
    // à ce nom composé ; la migration nomme la table "mises_a_lepreuve".
    protected $table = 'mises_a_lepreuve';

    /**
     * @return BelongsTo<DossierExecution, $this>
     */
    public function dossierExecution(): BelongsTo
    {
        return $this->belongsTo(DossierExecution::class);
    }
}
