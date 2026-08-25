<?php

namespace App\Domain\Instruction\Models;

use App\Domain\Contracts\Auditable;
use App\Domain\Personnes\Models\Personne;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'dossier_instruction_id', 'personne_id', 'type', 'debut_at', 'duree_jours',
    'fin_prevue_at', 'renouvele_le', 'obligations', 'statut', 'fin_reelle_at',
    'motif_fin', 'autorise_par',
])]
class MesureSurete extends Model implements Auditable
{
    // La pluralisation par défaut d'Eloquent donnerait "mesure_suretes".
    protected $table = 'mesures_surete';

    protected function casts(): array
    {
        return [
            'debut_at' => 'datetime',
            'fin_prevue_at' => 'datetime',
            'renouvele_le' => 'datetime',
            'fin_reelle_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DossierInstruction, $this>
     */
    public function dossierInstruction(): BelongsTo
    {
        return $this->belongsTo(DossierInstruction::class);
    }

    /**
     * @return BelongsTo<Personne, $this>
     */
    public function personne(): BelongsTo
    {
        return $this->belongsTo(Personne::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function autorisePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorise_par');
    }

    public function estEnCours(): bool
    {
        return $this->statut === 'en_cours';
    }

    public function echeanceDepassee(): bool
    {
        return $this->estEnCours() && $this->fin_prevue_at !== null && $this->fin_prevue_at->isPast();
    }

    public function auditableRepresentation(): array
    {
        return [
            'mesure_surete_id' => $this->id,
            'dossier_instruction_id' => $this->dossier_instruction_id,
            'personne_id' => $this->personne_id,
            'type' => $this->type,
        ];
    }
}
