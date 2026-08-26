<?php

namespace App\Domain\Casier\Models;

use App\Domain\Contracts\Auditable;
use App\Domain\Personnes\Models\Personne;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trace d'une consultation nominative du casier (§6.10). Elle-même
 * Auditable : le journal d'audit général doit pouvoir montrer qui a
 * consulté quel bulletin, en plus de cette table dédiée aux requêtes
 * directes (« historique des consultations d'une personne »).
 */
#[Fillable(['personne_id', 'type_bulletin', 'motif', 'consultee_at', 'consultee_par'])]
class Consultation extends Model implements Auditable
{
    protected $table = 'casier_consultations';

    protected function casts(): array
    {
        return [
            'consultee_at' => 'datetime',
        ];
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
    public function consultePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultee_par');
    }

    public function auditableRepresentation(): array
    {
        return [
            'consultation_id' => $this->id,
            'personne_id' => $this->personne_id,
            'type_bulletin' => $this->type_bulletin,
        ];
    }
}
