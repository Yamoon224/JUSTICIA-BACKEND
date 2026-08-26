<?php

namespace App\Domain\Personnes\Models;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Contracts\Auditable;
use App\Domain\Documents\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Fichier central des personnes mises en cause (§6.2). Le statut d'une
 * personne (suspect, mis en examen, condamné, relaxé...) n'est jamais porté
 * ici : il dépend de l'affaire (voir la table pivot affaire_personne), pour
 * respecter la présomption d'innocence — une même personne peut être
 * condamnée sur une affaire et relaxée sur une autre.
 */
#[Fillable([
    'identifiant_unique', 'type', 'nom', 'prenom', 'alias', 'date_naissance',
    'lieu_naissance', 'sexe', 'nom_pere', 'nom_mere', 'raison_sociale',
    'representant_legal_id', 'adresse', 'signalement', 'fusionnee_dans_id', 'created_by',
])]
class Personne extends Model implements Auditable
{
    protected function casts(): array
    {
        return [
            'alias' => 'array',
            'date_naissance' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Personne, $this>
     */
    public function representantLegal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'representant_legal_id');
    }

    /**
     * @return BelongsTo<Personne, $this>
     */
    public function fusionneeDans(): BelongsTo
    {
        return $this->belongsTo(self::class, 'fusionnee_dans_id');
    }

    /**
     * @return HasMany<PersonnePieceIdentite, $this>
     */
    public function piecesIdentite(): HasMany
    {
        return $this->hasMany(PersonnePieceIdentite::class);
    }

    /**
     * @return BelongsToMany<Affaire, $this>
     */
    public function affaires(): BelongsToMany
    {
        return $this->belongsToMany(Affaire::class, 'affaire_personne')
            ->withPivot(['statut', 'depuis'])
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Photos, pièces d'identité numérisées (§6.2, §9).
     *
     * @return MorphMany<Document, $this>
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function nomAffichage(): string
    {
        return $this->type === 'morale'
            ? (string) $this->raison_sociale
            : trim("{$this->prenom} {$this->nom}");
    }

    public function auditableRepresentation(): array
    {
        return [
            'personne_id' => $this->id,
            'identifiant_unique' => $this->identifiant_unique,
            'type' => $this->type,
        ];
    }
}
