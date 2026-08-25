<?php

namespace App\Domain\Audiencement\Models;

use App\Domain\Contracts\Auditable;
use App\Domain\Personnes\Models\Personne;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'decision_id', 'type', 'formee_par_personne_id', 'formee_at', 'recevable',
    'effet_suspensif', 'decision_recours', 'decision_recours_at', 'enregistre_par',
])]
class Recours extends Model implements Auditable
{
    protected function casts(): array
    {
        return [
            'formee_at' => 'datetime',
            'recevable' => 'boolean',
            'effet_suspensif' => 'boolean',
            'decision_recours_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Decision, $this>
     */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class);
    }

    /**
     * @return BelongsTo<Personne, $this>
     */
    public function formeParPersonne(): BelongsTo
    {
        return $this->belongsTo(Personne::class, 'formee_par_personne_id');
    }

    public function estResolu(): bool
    {
        return $this->decision_recours !== null;
    }

    public function auditableRepresentation(): array
    {
        return [
            'recours_id' => $this->id,
            'decision_id' => $this->decision_id,
            'type' => $this->type,
        ];
    }
}
