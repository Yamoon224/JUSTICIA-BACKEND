<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Service/institution de la chaîne pénale (§4, §6.13) : Police, Gendarmerie,
 * Parquet, Cabinet d'instruction, Juridiction, Greffe, Administration
 * pénitentiaire, Service du casier, DSI Justice.
 */
#[Fillable(['code', 'nom', 'type', 'parent_id', 'actif'])]
class Service extends Model
{
    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Service, $this>
     */
    public function enfants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function agents(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
