<?php

namespace App\Domain\Affaires\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Mouvement de la chaîne de conservation d'un scellé (§6.4). Append-only au
 * même titre que le journal d'audit : un mouvement enregistré ne se corrige
 * jamais, on en ajoute un nouveau pour refléter la réalité.
 */
#[Fillable(['scelle_id', 'type', 'remettant_id', 'recepteur_id', 'motif', 'horodatage'])]
class ScelleMouvement extends Model
{
    protected function casts(): array
    {
        return [
            'horodatage' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Un mouvement de scellé est append-only : il ne peut pas être modifié.'));
        static::deleting(fn () => throw new LogicException('Un mouvement de scellé est append-only : il ne peut pas être supprimé.'));
    }

    /**
     * @return BelongsTo<Scelle, $this>
     */
    public function scelle(): BelongsTo
    {
        return $this->belongsTo(Scelle::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function remettant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'remettant_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recepteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recepteur_id');
    }
}
