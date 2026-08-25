<?php

namespace App\Domain\Affaires\Models;

use App\Domain\Contracts\Auditable;
use App\Domain\Personnes\Models\Personne;
use App\Models\Infraction;
use App\Models\Ressort;
use App\Models\Unite;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Dossier d'affaire (§6.3) : numéro unique conservé de bout en bout. Le
 * ressort est la clé du cloisonnement des habilitations (§8) — voir
 * App\Policies\AffairePolicy.
 */
#[Fillable([
    'numero_affaire', 'unite_id', 'ressort_id', 'statut', 'description',
    'date_ouverture', 'affaire_jointe_a_id', 'created_by',
])]
class Affaire extends Model implements Auditable
{
    protected function casts(): array
    {
        return [
            'date_ouverture' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Unite, $this>
     */
    public function unite(): BelongsTo
    {
        return $this->belongsTo(Unite::class);
    }

    /**
     * @return BelongsTo<Ressort, $this>
     */
    public function ressort(): BelongsTo
    {
        return $this->belongsTo(Ressort::class);
    }

    /**
     * @return BelongsTo<Affaire, $this>
     */
    public function affaireJointeA(): BelongsTo
    {
        return $this->belongsTo(self::class, 'affaire_jointe_a_id');
    }

    /**
     * @return HasMany<Affaire, $this>
     */
    public function affairesJointes(): HasMany
    {
        return $this->hasMany(self::class, 'affaire_jointe_a_id');
    }

    /**
     * @return BelongsToMany<Infraction, $this>
     */
    public function infractions(): BelongsToMany
    {
        return $this->belongsToMany(Infraction::class, 'affaire_infraction')->withTimestamps();
    }

    /**
     * @return BelongsToMany<Personne, $this>
     */
    public function personnes(): BelongsToMany
    {
        return $this->belongsToMany(Personne::class, 'affaire_personne')
            ->withPivot(['statut', 'depuis'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<ProcesVerbal, $this>
     */
    public function procesVerbaux(): HasMany
    {
        return $this->hasMany(ProcesVerbal::class);
    }

    /**
     * @return HasMany<Scelle, $this>
     */
    public function scelles(): HasMany
    {
        return $this->hasMany(Scelle::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function auditableRepresentation(): array
    {
        return [
            'affaire_id' => $this->id,
            'numero_affaire' => $this->numero_affaire,
            'ressort_id' => $this->ressort_id,
        ];
    }
}
