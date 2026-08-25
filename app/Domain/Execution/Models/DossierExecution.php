<?php

namespace App\Domain\Execution\Models;

use App\Domain\Audiencement\Models\Decision;
use App\Domain\Contracts\Auditable;
use App\Domain\Personnes\Models\Personne;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['decision_id', 'personne_id', 'statut', 'mise_a_execution_at', 'mise_a_execution_par'])]
class DossierExecution extends Model implements Auditable
{
    // La pluralisation par défaut d'Eloquent donnerait "dossier_executions" ;
    // la migration nomme la table au pluriel sur "dossiers", pas "execution".
    protected $table = 'dossiers_execution';

    protected function casts(): array
    {
        return [
            'mise_a_execution_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Decision, $this>
     */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class);
    }

    /**
     * @return BelongsTo<Personne, $this>
     */
    public function personne(): BelongsTo
    {
        return $this->belongsTo(Personne::class);
    }

    /**
     * @return HasOne<Ecrou, $this>
     */
    public function ecrou(): HasOne
    {
        return $this->hasOne(Ecrou::class);
    }

    /**
     * @return HasOne<Amende, $this>
     */
    public function amende(): HasOne
    {
        return $this->hasOne(Amende::class);
    }

    /**
     * @return HasOne<TravailInteretGeneral, $this>
     */
    public function tig(): HasOne
    {
        return $this->hasOne(TravailInteretGeneral::class);
    }

    /**
     * @return HasOne<MiseALEpreuve, $this>
     */
    public function miseALEpreuve(): HasOne
    {
        return $this->hasOne(MiseALEpreuve::class);
    }

    public function auditableRepresentation(): array
    {
        return [
            'dossier_execution_id' => $this->id,
            'decision_id' => $this->decision_id,
            'personne_id' => $this->personne_id,
        ];
    }
}
