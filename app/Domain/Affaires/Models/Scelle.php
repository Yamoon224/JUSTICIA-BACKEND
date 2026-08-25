<?php

namespace App\Domain\Affaires\Models;

use App\Domain\Contracts\Auditable;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['affaire_id', 'numero_scelle', 'description', 'lieu_saisie', 'statut', 'created_by'])]
class Scelle extends Model implements Auditable
{
    /**
     * @return BelongsTo<Affaire, $this>
     */
    public function affaire(): BelongsTo
    {
        return $this->belongsTo(Affaire::class);
    }

    /**
     * @return HasMany<ScelleMouvement, $this>
     */
    public function mouvements(): HasMany
    {
        return $this->hasMany(ScelleMouvement::class);
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
            'scelle_id' => $this->id,
            'numero_scelle' => $this->numero_scelle,
            'affaire_id' => $this->affaire_id,
        ];
    }
}
