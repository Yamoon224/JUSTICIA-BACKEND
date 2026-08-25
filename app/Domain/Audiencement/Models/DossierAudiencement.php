<?php

namespace App\Domain\Audiencement\Models;

use App\Domain\Affaires\Models\Affaire;
use App\Domain\Contracts\Auditable;
use App\Models\Juridiction;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'affaire_id', 'juridiction_id', 'chambre', 'date_audience',
    'president_id', 'greffier_id', 'statut', 'cree_at',
])]
class DossierAudiencement extends Model implements Auditable
{
    // La pluralisation par défaut d'Eloquent donnerait
    // "dossier_audiencements" ; la migration nomme la table au pluriel sur
    // "dossiers", pas "audiencement".
    protected $table = 'dossiers_audiencement';

    protected function casts(): array
    {
        return [
            'date_audience' => 'datetime',
            'cree_at' => 'datetime',
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
     * @return BelongsTo<Juridiction, $this>
     */
    public function juridiction(): BelongsTo
    {
        return $this->belongsTo(Juridiction::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function president(): BelongsTo
    {
        return $this->belongsTo(User::class, 'president_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function greffier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'greffier_id');
    }

    /**
     * @return HasMany<RenvoiAudience, $this>
     */
    public function renvois(): HasMany
    {
        return $this->hasMany(RenvoiAudience::class);
    }

    /**
     * @return HasMany<Decision, $this>
     */
    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class);
    }

    public function estEnrole(): bool
    {
        return $this->statut !== 'a_enroler';
    }

    public function auditableRepresentation(): array
    {
        return [
            'dossier_audiencement_id' => $this->id,
            'affaire_id' => $this->affaire_id,
        ];
    }
}
