<?php

namespace App\Domain\Audiencement\Models;

use App\Domain\Contracts\Auditable;
use App\Domain\Execution\Models\DossierExecution;
use App\Domain\Personnes\Models\Personne;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Décision par prévenu (§6.7). Le caractère définitif (estDefinitive) se
 * calcule à la volée depuis le délai de recours et l'existence d'un recours
 * recevable — jamais stocké comme un simple booléen qui pourrait devenir
 * incohérent avec les faits (§6.7 : « à expiration sans recours, la
 * décision devient définitive »).
 */
#[Fillable([
    'dossier_audiencement_id', 'personne_id', 'decision', 'peine_principale',
    'sursis', 'interets_civils', 'rendue_at', 'delai_recours_jours',
    'delai_recours_expire_at', 'rendue_par',
])]
class Decision extends Model implements Auditable
{
    protected function casts(): array
    {
        return [
            'sursis' => 'boolean',
            'rendue_at' => 'datetime',
            'delai_recours_expire_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DossierAudiencement, $this>
     */
    public function dossierAudiencement(): BelongsTo
    {
        return $this->belongsTo(DossierAudiencement::class);
    }

    /**
     * @return BelongsTo<Personne, $this>
     */
    public function personne(): BelongsTo
    {
        return $this->belongsTo(Personne::class);
    }

    /**
     * @return HasMany<Recours, $this>
     */
    public function recours(): HasMany
    {
        return $this->hasMany(Recours::class);
    }

    /**
     * @return HasOne<DossierExecution, $this>
     */
    public function dossierExecution(): HasOne
    {
        return $this->hasOne(DossierExecution::class);
    }

    /**
     * Un recours recevable et non encore résolu suspend le caractère
     * définitif indéfiniment (§6.8) ; sans recours recevable, la décision
     * devient définitive à l'expiration du délai (§6.7).
     */
    public function estDefinitive(): bool
    {
        if ($this->recours()->where('recevable', true)->exists()) {
            return false;
        }

        return $this->delai_recours_expire_at->isPast();
    }

    public function auditableRepresentation(): array
    {
        return [
            'decision_id' => $this->id,
            'dossier_audiencement_id' => $this->dossier_audiencement_id,
            'personne_id' => $this->personne_id,
            'decision' => $this->decision,
        ];
    }
}
