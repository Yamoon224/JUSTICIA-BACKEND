<?php

namespace App\Domain\Casier\Models;

use App\Domain\Audiencement\Models\Decision;
use App\Domain\Contracts\Auditable;
use App\Domain\Personnes\Models\Personne;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Condamnation inscrite au casier judiciaire (§6.10). Instantané figé au
 * moment de l'inscription (numero_affaire, juridiction_nom,
 * infraction_libelle) : ne dérive jamais ces champs à la volée depuis la
 * décision source, pour que le contenu d'un bulletin reste stable et
 * opposable même si le dossier d'origine évolue ensuite.
 */
#[Fillable([
    'personne_id', 'decision_id', 'numero_affaire', 'juridiction_nom',
    'infraction_libelle', 'categorie_infraction', 'peine_principale', 'sursis',
    'condamnee_at', 'statut', 'inscrite_at',
])]
class Condamnation extends Model implements Auditable
{
    // La pluralisation par défaut d'Eloquent donnerait "condamnations" sans
    // le préfixe du module ; la migration nomme la table "casier_condamnations".
    protected $table = 'casier_condamnations';

    protected function casts(): array
    {
        return [
            'sursis' => 'boolean',
            'condamnee_at' => 'datetime',
            'inscrite_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Personne, $this>
     */
    public function personne(): BelongsTo
    {
        return $this->belongsTo(Personne::class);
    }

    /**
     * @return BelongsTo<Decision, $this>
     */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class);
    }

    /**
     * @return HasOne<Rehabilitation, $this>
     */
    public function rehabilitation(): HasOne
    {
        return $this->hasOne(Rehabilitation::class);
    }

    /**
     * @return HasOne<Amnistie, $this>
     */
    public function amnistie(): HasOne
    {
        return $this->hasOne(Amnistie::class);
    }

    public function estActive(): bool
    {
        return $this->statut === 'active';
    }

    public function auditableRepresentation(): array
    {
        return [
            'condamnation_id' => $this->id,
            'personne_id' => $this->personne_id,
            'statut' => $this->statut,
        ];
    }
}
