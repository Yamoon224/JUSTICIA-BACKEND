<?php

namespace App\Domain\Execution\Models;

use App\Domain\Contracts\Auditable;
use App\Domain\Personnes\Models\Personne;
use App\Models\EtablissementPenitentiaire;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Écrou (§6.9). La situation pénale (date_fin_prevue) est recalculée à
 * chaque remise de peine (EnregistrerRemiseDePeineAction) plutôt que
 * dérivée à la volée : une échéance de libération doit rester une valeur
 * stable et interrogeable directement par le moteur d'alertes
 * (DetecterEcheancesLiberationAction), sans recalcul implicite.
 */
#[Fillable([
    'dossier_execution_id', 'numero_ecrou', 'personne_id', 'etablissement_id',
    'date_ecrou', 'duree_jours', 'detention_provisoire_imputee_jours',
    'date_fin_prevue', 'statut', 'date_liberation', 'motif_liberation', 'ecroue_par',
])]
class Ecrou extends Model implements Auditable
{
    protected function casts(): array
    {
        return [
            'date_ecrou' => 'datetime',
            'date_fin_prevue' => 'datetime',
            'date_liberation' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DossierExecution, $this>
     */
    public function dossierExecution(): BelongsTo
    {
        return $this->belongsTo(DossierExecution::class);
    }

    /**
     * @return BelongsTo<Personne, $this>
     */
    public function personne(): BelongsTo
    {
        return $this->belongsTo(Personne::class);
    }

    /**
     * @return BelongsTo<EtablissementPenitentiaire, $this>
     */
    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(EtablissementPenitentiaire::class);
    }

    /**
     * @return HasMany<RemisePeine, $this>
     */
    public function remisesPeine(): HasMany
    {
        return $this->hasMany(RemisePeine::class);
    }

    /**
     * @return HasMany<AmenagementPeine, $this>
     */
    public function amenagements(): HasMany
    {
        return $this->hasMany(AmenagementPeine::class);
    }

    /**
     * @return HasMany<TransfertEcrou, $this>
     */
    public function transferts(): HasMany
    {
        return $this->hasMany(TransfertEcrou::class);
    }

    public function estEnDetention(): bool
    {
        return $this->statut === 'en_detention';
    }

    public function echeanceDepassee(): bool
    {
        return $this->estEnDetention() && $this->date_fin_prevue->isPast();
    }

    public function auditableRepresentation(): array
    {
        return [
            'ecrou_id' => $this->id,
            'numero_ecrou' => $this->numero_ecrou,
            'personne_id' => $this->personne_id,
        ];
    }
}
