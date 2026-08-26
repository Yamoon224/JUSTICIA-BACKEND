<?php

namespace App\Domain\Alertes\Models;

use App\Domain\Contracts\Auditable;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Alerte personnelle routée vers un agent (§6.1, §6.11) — le résultat
 * qualifié d'un moteur de délais (DetecterEcheancesGardeAVueAction,
 * DetecterEcheancesDetentionAction), pas un nouveau calcul.
 */
#[Fillable(['type', 'niveau', 'message', 'alertable_type', 'alertable_id', 'destinataire_id', 'lue_at'])]
class Alerte extends Model implements Auditable
{
    protected function casts(): array
    {
        return [
            'lue_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function alertable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function destinataire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'destinataire_id');
    }

    public function estLue(): bool
    {
        return $this->lue_at !== null;
    }

    public function auditableRepresentation(): array
    {
        return [
            'alerte_id' => $this->id,
            'type' => $this->type,
            'niveau' => $this->niveau,
            'destinataire_id' => $this->destinataire_id,
        ];
    }
}
