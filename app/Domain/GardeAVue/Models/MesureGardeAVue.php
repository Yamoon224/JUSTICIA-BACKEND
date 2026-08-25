<?php

namespace App\Domain\GardeAVue\Models;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Contracts\Auditable;
use App\Domain\Personnes\Models\Personne;
use App\Models\Unite;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mesure de garde à vue (§6.1). L'échéance légale (`fin_prevue_at`) est
 * calculée une fois pour toutes au placement à partir du référentiel
 * `delais_legaux` (voir PlacerEnGardeAVueAction) — jamais recalculée à la
 * volée côté client (§10.2 : le frontend affiche, le backend décide).
 */
#[Fillable([
    'affaire_id', 'personne_id', 'unite_id', 'debut_at', 'duree_heures',
    'fin_prevue_at', 'mineur', 'created_by', 'statut', 'issue', 'fin_reelle_at',
    'prolongation_heures', 'prolongation_autorisee_par', 'prolongation_at',
    'avis_representant_legal_at',
])]
class MesureGardeAVue extends Model implements Auditable
{
    // La pluralisation par défaut d'Eloquent donnerait "mesure_garde_a_vues".
    protected $table = 'mesures_garde_a_vue';

    protected function casts(): array
    {
        return [
            'debut_at' => 'datetime',
            'fin_prevue_at' => 'datetime',
            'prolongation_at' => 'datetime',
            'fin_reelle_at' => 'datetime',
            'avis_representant_legal_at' => 'datetime',
            'mineur' => 'boolean',
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
     * @return BelongsTo<Personne, $this>
     */
    public function personne(): BelongsTo
    {
        return $this->belongsTo(Personne::class);
    }

    /**
     * @return BelongsTo<Unite, $this>
     */
    public function unite(): BelongsTo
    {
        return $this->belongsTo(Unite::class);
    }

    /**
     * @return HasMany<GavActe, $this>
     */
    public function actes(): HasMany
    {
        return $this->hasMany(GavActe::class, 'mesure_id');
    }

    /**
     * @return HasMany<GavNotificationDroit, $this>
     */
    public function notificationsDroits(): HasMany
    {
        return $this->hasMany(GavNotificationDroit::class, 'mesure_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function estEnCours(): bool
    {
        return $this->statut !== 'terminee';
    }

    public function echeanceDepassee(): bool
    {
        return $this->estEnCours() && $this->fin_prevue_at->isPast();
    }

    public function auditableRepresentation(): array
    {
        return [
            'mesure_garde_a_vue_id' => $this->id,
            'affaire_id' => $this->affaire_id,
            'personne_id' => $this->personne_id,
        ];
    }
}
