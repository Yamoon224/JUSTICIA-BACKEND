<?php

namespace App\Domain\Execution\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dossier_execution_id', 'heures_requises', 'heures_effectuees', 'affecte_a', 'statut'])]
class TravailInteretGeneral extends Model
{
    // La pluralisation anglaise naïve donnerait "travail_interet_generals" ;
    // la migration reprend le pluriel français irrégulier "travaux".
    protected $table = 'travaux_interet_general';

    /**
     * @return BelongsTo<DossierExecution, $this>
     */
    public function dossierExecution(): BelongsTo
    {
        return $this->belongsTo(DossierExecution::class);
    }

    public function estTermine(): bool
    {
        return $this->statut === 'terminee';
    }
}
