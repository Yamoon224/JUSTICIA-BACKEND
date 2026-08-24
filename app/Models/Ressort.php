<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ressort territorial (national, cour d'appel, tribunal) — §6.13, §8.
 * Base du cloisonnement des habilitations par ressort.
 */
#[Fillable(['code', 'nom', 'type', 'parent_id'])]
class Ressort extends Model
{
    /**
     * @return BelongsTo<Ressort, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Ressort, $this>
     */
    public function enfants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
