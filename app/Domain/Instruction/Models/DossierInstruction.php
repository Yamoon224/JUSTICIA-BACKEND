<?php

namespace App\Domain\Instruction\Models;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Contracts\Auditable;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'affaire_id', 'juge_instruction_id', 'ouvert_at', 'statut',
    'ordonnance', 'ordonnance_at', 'ordonnance_par', 'delai_recours_expire_at',
])]
class DossierInstruction extends Model implements Auditable
{
    // La pluralisation par défaut d'Eloquent donnerait
    // "dossier_instructions" ; la migration nomme la table au pluriel sur
    // "dossiers", pas "instruction".
    protected $table = 'dossiers_instruction';

    protected function casts(): array
    {
        return [
            'ouvert_at' => 'datetime',
            'ordonnance_at' => 'datetime',
            'delai_recours_expire_at' => 'datetime',
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
    public function jugeInstruction(): BelongsTo
    {
        return $this->belongsTo(User::class, 'juge_instruction_id');
    }

    /**
     * @return HasMany<ActeInstruction, $this>
     */
    public function actes(): HasMany
    {
        return $this->hasMany(ActeInstruction::class);
    }

    /**
     * @return HasMany<Mandat, $this>
     */
    public function mandats(): HasMany
    {
        return $this->hasMany(Mandat::class);
    }

    /**
     * @return HasMany<MesureSurete, $this>
     */
    public function mesuresSurete(): HasMany
    {
        return $this->hasMany(MesureSurete::class);
    }

    public function estEnCours(): bool
    {
        return $this->statut === 'en_cours';
    }

    public function auditableRepresentation(): array
    {
        return [
            'dossier_instruction_id' => $this->id,
            'affaire_id' => $this->affaire_id,
        ];
    }
}
