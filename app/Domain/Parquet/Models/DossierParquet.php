<?php

namespace App\Domain\Parquet\Models;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Contracts\Auditable;
use App\Models\MotifClassement;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Dossier au bureau des arrivées du parquet (§6.5). Une affaire n'a au plus
 * qu'un seul dossier parquet — le renvoi éventuel (cassation avec renvoi,
 * réouverture...) sera modélisé en Phase 5/8, pas ici.
 */
#[Fillable([
    'affaire_id', 'magistrat_id', 'recu_at', 'affecte_at', 'orientation',
    'motif_classement_id', 'oriente_at', 'oriente_par',
])]
class DossierParquet extends Model implements Auditable
{
    // La pluralisation par défaut d'Eloquent donnerait "dossier_parquets" ;
    // la migration nomme la table au pluriel sur "dossiers", pas "parquet".
    protected $table = 'dossiers_parquet';

    protected function casts(): array
    {
        return [
            'recu_at' => 'datetime',
            'affecte_at' => 'datetime',
            'oriente_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Affaire, $this>
     */
    public function affaire(): BelongsTo
    {
        return $this->belongsTo(Affaire::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function magistrat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'magistrat_id');
    }

    /**
     * @return BelongsTo<MotifClassement, $this>
     */
    public function motifClassement(): BelongsTo
    {
        return $this->belongsTo(MotifClassement::class);
    }

    /**
     * @return HasMany<Requisition, $this>
     */
    public function requisitions(): HasMany
    {
        return $this->hasMany(Requisition::class);
    }

    public function auditableRepresentation(): array
    {
        return [
            'dossier_parquet_id' => $this->id,
            'affaire_id' => $this->affaire_id,
        ];
    }
}
